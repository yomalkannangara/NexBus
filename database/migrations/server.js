/**
 * NexBus Demo API (dummy live bus locations)
 *
 * Endpoints (kept same):
 *   GET  /health
 *   GET  /api/buses/lives
 *   GET  /api/routes
 *
 * Install:
 *   npm i express cors mysql2
 *
 * Run:
 *   PORT=4000 node server.js
 */

const express = require("express");
const cors = require("cors");

let mysql = null;
try {
  mysql = require("mysql2/promise");
} catch (_err) {
  mysql = null;
}

const app = express();
app.use(cors());
app.use(express.json());

const PORT = Number(process.env.PORT || 4000);
const TICK_MS = parseInt(process.env.TICK_MS || "1000", 10); // 1 second updates
const WAIT_STOP_MINUTES = Math.max(10, Number(process.env.WAIT_STOP_MINUTES || 10));
const TIMETABLE_REFRESH_MS = parseInt(process.env.TIMETABLE_REFRESH_MS || `${5 * 60 * 1000}`, 10);

const DB_CONFIG = {
  host: process.env.DB_HOST || "127.0.0.1",
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || "root",
  password: process.env.DB_PASSWORD || "",
  database: process.env.DB_NAME || "nexbus",
  waitForConnections: true,
  connectionLimit: 5,
  queueLimit: 0,
};

let dbPool = null;
let timetableLoadInProgress = false;
const timetableState = {
  schedulesByBus: new Map(),
  primaryRouteByBus: new Map(),
  loadedAt: 0,
};

function makeRegRange(prefix, from, to, width = 0) {
  const out = [];
  for (let n = from; n <= to; n += 1) {
    out.push(`${prefix}${String(n).padStart(width, "0")}`);
  }
  return out;
}

// -----------------------------------------------------------------------------
// Seeded bus numbers (aligned to nexbus_full_install_from_scratch.sql)
// -----------------------------------------------------------------------------
const SLTB_BUS_IDS = [
  // From sltb_buses
  "213123",
  "NA-2024",
  "NA-2025",
  "NA-2030",
  "NA-23233",
  "NA-2420",
  "NA-2581",
  "NA-2589",
  "NA-2592",
  "NA-2594",
  "NA-7890",
  "NB-001",
  "NB-002",
  "NB-003",
  "NB-004",
  "NB-005",
  "NB-006",
  "NB-007",
  "NB-008",
  "NB-009",
  "NB-010",
  "NB-011",
  "NB-012",
  "NB-013",
  "NB-014",
  "NB-015",
  "NB-016",
  "NB-017",
  "NB-018",
  "NB-019",
  "NB-020",
  "NB-021",
  "NB-022",
  "NB-023",
  "NB-024",
  "NB-025",
  "NB-026",
  "NB-027",
  "NB-028",
  "NB-029",
  "NB-030",
  "NB-1001",
  "NB-1002",
  "NB-1003",
  "NB-1004",
  "NB-1005",
  "NB-1006",
  "NB-1007",
  "NB-1008",
  "NB-1009",
  "NB-1010",
  "NB-2001",
  "NB-2002",
  "NB-2590",
  "NB-2591",
  "NB-2593",
  "NB-3001",
  "NB-3002",
  "NB-3101",
  "NB-3102",
  "NB-3103",
  "NB-3104",
  "NB-3105",
  "NB-3106",
  "NB-3107",
  "NB-3108",
  "NB-3109",
  "NB-3110",
  "NB-3111",
  "NB-3112",
  "NB-5667",
  "NB2341",
  "PA-1001",
  "PB-1002",
  "TEST-1234",

  // Additional SLTB slice added in the realistic seed
  ...makeRegRange("NB-", 4501, 4510, 4),

  // Coverage-guarantee buses for depots that have no ACTIVE buses after seed inserts
  "NB-AUTO-00004",
  ...makeRegRange("NB-AUTO-", 103, 113, 5),
  ...makeRegRange("NB-AUTO-", 115, 125, 5),
];

// Private bus IDs (from private_buses + coverage-guarantee PB-AUTO-* buses)
const PRIVATE_BUS_IDS = new Set([
  // From private_buses
  "213123",
  "NB2341",
  "PA-1002",
  "PA-1011",
  "PA-1012",
  "PB-1001",
  "PB-2001",
  "PB-2002",
  "PB-3001",
  "PB-3002",

  // 50 realistic private buses added in the realistic seed
  ...makeRegRange("PB-", 4001, 4050, 4),

  // Coverage-guarantee bus for private operators with no ACTIVE buses after seed inserts
  "PB-AUTO-00013",
]);

// Union (unique) of all seeded buses
const BUS_IDS = Array.from(new Set([...SLTB_BUS_IDS, ...PRIVATE_BUS_IDS]));

// Route assignment (derived from your timetables where available; otherwise assigned for demo)
const BUS_ROUTE = {
  "213123": "401",
  "NA-2024": "120",
  "NA-2025": "120",
  "NA-2030": "120",
  "NA-23233": "120",
  "NA-2420": "120",
  "NB-1001": "1",
  "NB-1002": "1",
  "NB-1003": "1",
  "NB-1004": "1",
  "NB-1005": "1",
  "NB-1006": "1",
  "NB-1007": "1",
  "NB-1008": "1",
  "NB-1009": "1",
  "NB-1010": "1",
  "NB-2001": "3",
  "NB-2002": "3",
  "NB-3001": "4",
  "NB-3002": "700",
  "NB-3101": "1",
  "NB-3102": "2",
  "NB-3103": "3",
  "NB-3104": "4",
  "NB-3105": "1",
  "NB-3106": "2",
  "NB-3107": "3",
  "NB-3108": "4",
  "NB-3109": "1",
  "NB-3110": "2",
  "NB-3111": "3",
  "NB-3112": "4",
  "NB-5667": "1",
  "NB2341": "1",
  "PA-1001": "120",
  "PA-1002": "120",
  "PB-1001": "122",
  "PB-1002": "2",
  "PB-2001": "401",
  "PB-2002": "401",
  "PB-3001": "700",
  "PB-3002": "700",
};

// -----------------------------------------------------------------------------
// Routes (polylines). Route 120 is aligned to the real Colombo↔Horana corridor (B84),
// using real-world waypoint coordinates (Fort → Kohuwala → Pepiliyana → Boralesgamuwa
// → Piliyandala → Kesbewa → Horana). Others are reasonable demo polylines.
// -----------------------------------------------------------------------------
const ROUTES = {
  // 1: Colombo → Kalutara → Hikkaduwa → Galle (demo)
  "1": {
    name: "Route 1 (Colombo → Kalutara → Hikkaduwa → Galle)",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 6.5854, lng: 79.9607 }, // Kalutara
      { lat: 6.14, lng: 80.1 }, // Hikkaduwa
      { lat: 6.0535, lng: 80.221 }, // Galle
    ],
  },

  // 2: Colombo → Avissawella → Kegalle → Kandy (demo)
  "2": {
    name: "Route 2 (Colombo → Avissawella → Kegalle → Kandy)",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 6.955, lng: 80.21 }, // Avissawella (approx)
      { lat: 7.252, lng: 80.3435 }, // Kegalle (approx)
      { lat: 7.2906, lng: 80.6337 }, // Kandy (approx)
    ],
  },

  // 3: Colombo → Wattala → Negombo (demo)
  "3": {
    name: "Route 3 (Colombo → Wattala → Negombo)",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 6.989, lng: 79.891 }, // Wattala (approx)
      { lat: 7.2086, lng: 79.8358 }, // Negombo (approx)
    ],
  },

  // 4: Colombo → Mt. Lavinia → Moratuwa → Panadura (demo)
  "4": {
    name: "Route 4 (Colombo → Mt. Lavinia → Moratuwa → Panadura)",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 6.837, lng: 79.865 }, // Mt. Lavinia (approx)
      { lat: 6.773, lng: 79.881 }, // Moratuwa (approx)
      { lat: 6.713, lng: 79.902 }, // Panadura (approx)
    ],
  },

  // 120: Colombo Fort → Kohuwala → Pepiliyana → Boralesgamuwa → Piliyandala → Kesbewa → Horana
  "120": {
    name: "Route 120 (Colombo Fort → Horana) – B84 corridor",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 6.915, lng: 79.864 }, // Town Hall / Thummulla area (approx)
      { lat: 6.8687, lng: 79.8899 }, // Kohuwala
      { lat: 6.857, lng: 79.8846 }, // Pepiliyana
      { lat: 6.8412, lng: 79.9025 }, // Boralesgamuwa
      { lat: 6.8018, lng: 79.9227 }, // Piliyandala
      { lat: 6.7905, lng: 79.9365 }, // Kesbewa
      { lat: 6.723, lng: 80.0647 }, // Horana
    ],
  },

  // 122: Colombo → Avissawella (demo)
  "122": {
    name: "Route 122 (Colombo → Avissawella)",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 6.955, lng: 80.21 }, // Avissawella (approx)
    ],
  },

  // 401: Colombo → Ja-Ela → Negombo (demo)
  "401": {
    name: "Route 401 (Colombo → Ja-Ela → Negombo)",
    polyline: [
      { lat: 6.9378, lng: 79.8437 }, // Colombo Fort
      { lat: 7.074, lng: 79.891 }, // Ja-Ela (approx)
      { lat: 7.2086, lng: 79.8358 }, // Negombo (approx)
    ],
  },

  // 700: Galle → Weligama → Matara (demo)
  "700": {
    name: "Route 700 (Galle → Weligama → Matara)",
    polyline: [
      { lat: 6.0535, lng: 80.221 }, // Galle
      { lat: 5.9667, lng: 80.4167 }, // Weligama (approx)
      { lat: 5.9496, lng: 80.535 }, // Matara (approx)
    ],
  },
};

// -----------------------------------------------------------------------------
// Helpers (movement)
// -----------------------------------------------------------------------------
const toRad = (deg) => (deg * Math.PI) / 180;
const toDeg = (rad) => (rad * 180) / Math.PI;

function haversineMeters(a, b) {
  const R = 6371000;
  const dLat = toRad(b.lat - a.lat);
  const dLon = toRad(b.lng - a.lng);
  const lat1 = toRad(a.lat);
  const lat2 = toRad(b.lat);

  const h =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(h));
}

function lerp(a, b, t) {
  return a + (b - a) * t;
}

function interpPoint(a, b, t) {
  return { lat: lerp(a.lat, b.lat, t), lng: lerp(a.lng, b.lng, t) };
}

// Bearing in degrees (0..360)
function bearingDeg(a, b) {
  const lat1 = toRad(a.lat);
  const lat2 = toRad(b.lat);
  const dLon = toRad(b.lng - a.lng);

  const y = Math.sin(dLon) * Math.cos(lat2);
  const x =
    Math.cos(lat1) * Math.sin(lat2) -
    Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon);
  const brng = Math.atan2(y, x);
  return (toDeg(brng) + 360) % 360;
}

// small GPS-like jitter (≈ 0–6m)
function jitter(p) {
  const meters = Math.random() * 6;
  const angle = Math.random() * Math.PI * 2;
  // rough conversion meters->degrees
  const dLat = (meters * Math.cos(angle)) / 111320;
  const dLng = (meters * Math.sin(angle)) / (111320 * Math.cos(toRad(p.lat)));
  return { lat: p.lat + dLat, lng: p.lng + dLng };
}

function getDbPool() {
  if (!mysql) return null;
  if (!dbPool) {
    dbPool = mysql.createPool(DB_CONFIG);
  }
  return dbPool;
}

function parseClockToMinutes(value) {
  if (value == null) return null;
  const raw = String(value).trim();
  if (!raw) return null;

  const parts = raw.split(":");
  if (parts.length < 2) return null;

  const hh = Number(parts[0]);
  const mm = Number(parts[1]);
  const ss = Number(parts[2] || 0);
  if (![hh, mm, ss].every((n) => Number.isFinite(n))) return null;

  return hh * 60 + mm + ss / 60;
}

const routeMetricsCache = new Map();

function getRouteMetrics(routeNo) {
  if (routeMetricsCache.has(routeNo)) {
    return routeMetricsCache.get(routeNo);
  }

  const route = ROUTES[routeNo] || ROUTES["120"];
  const poly = route && route.polyline ? route.polyline : [];
  const segments = [];
  let totalMeters = 0;

  for (let i = 0; i < poly.length - 1; i += 1) {
    const len = Math.max(1, haversineMeters(poly[i], poly[i + 1]));
    segments.push(len);
    totalMeters += len;
  }

  const out = {
    segments,
    totalMeters: Math.max(1, totalMeters),
  };
  routeMetricsCache.set(routeNo, out);
  return out;
}

function pointAlongRoute(routeNo, progress) {
  const route = ROUTES[routeNo] || ROUTES["120"];
  const poly = route && route.polyline ? route.polyline : [];

  if (poly.length < 2) {
    const p = poly[0] || { lat: 0, lng: 0 };
    return {
      point: p,
      heading: 0,
      totalMeters: 1,
    };
  }

  const metrics = getRouteMetrics(routeNo);
  let remaining = metrics.totalMeters * Math.max(0, Math.min(1, progress));

  for (let i = 0; i < metrics.segments.length; i += 1) {
    const segLen = metrics.segments[i];
    if (remaining <= segLen || i === metrics.segments.length - 1) {
      const t = segLen <= 0 ? 0 : Math.max(0, Math.min(1, remaining / segLen));
      return {
        point: interpPoint(poly[i], poly[i + 1], t),
        heading: bearingDeg(poly[i], poly[i + 1]),
        totalMeters: metrics.totalMeters,
      };
    }
    remaining -= segLen;
  }

  const last = poly[poly.length - 1];
  return {
    point: last,
    heading: bearingDeg(poly[poly.length - 2], last),
    totalMeters: metrics.totalMeters,
  };
}

function normalizeScheduleRow(row) {
  const busRegNo = String(row.bus_reg_no || "").trim();
  if (!busRegNo) return null;

  const dbRouteNo = String(row.route_no || "").trim();
  const fallbackRoute = BUS_ROUTE[busRegNo];
  const routeNo = ROUTES[dbRouteNo] ? dbRouteNo : fallbackRoute;
  if (!routeNo || !ROUTES[routeNo]) return null;

  const dayOfWeek = Number(row.day_of_week);
  if (!Number.isFinite(dayOfWeek) || dayOfWeek < 0 || dayOfWeek > 6) return null;

  const departureMin = parseClockToMinutes(row.departure_time);
  const arrivalMin = parseClockToMinutes(row.arrival_time);
  if (departureMin == null || arrivalMin == null) return null;

  return {
    busRegNo,
    routeNo: String(routeNo),
    dayOfWeek,
    departureMin,
    arrivalMin,
  };
}

async function refreshTimetablesFromDb() {
  if (timetableLoadInProgress) return;
  const pool = getDbPool();
  if (!pool) return;

  timetableLoadInProgress = true;
  try {
    const [rows] = await pool.query(`
      SELECT
        t.bus_reg_no,
        t.day_of_week,
        CAST(t.departure_time AS CHAR) AS departure_time,
        CAST(t.arrival_time AS CHAR) AS arrival_time,
        COALESCE(r.route_no, '') AS route_no
      FROM timetables t
      LEFT JOIN routes r ON r.route_id = t.route_id
      WHERE t.bus_reg_no IS NOT NULL
        AND t.departure_time IS NOT NULL
        AND t.arrival_time IS NOT NULL
    `);

    const schedulesByBus = new Map();
    const primaryRouteByBus = new Map();

    for (const row of rows) {
      const schedule = normalizeScheduleRow(row);
      if (!schedule) continue;

      if (!schedulesByBus.has(schedule.busRegNo)) {
        schedulesByBus.set(schedule.busRegNo, []);
      }
      schedulesByBus.get(schedule.busRegNo).push(schedule);

      if (!primaryRouteByBus.has(schedule.busRegNo)) {
        primaryRouteByBus.set(schedule.busRegNo, schedule.routeNo);
      }
    }

    for (const schedules of schedulesByBus.values()) {
      schedules.sort((a, b) => {
        if (a.dayOfWeek !== b.dayOfWeek) return a.dayOfWeek - b.dayOfWeek;
        return a.departureMin - b.departureMin;
      });
    }

    timetableState.schedulesByBus = schedulesByBus;
    timetableState.primaryRouteByBus = primaryRouteByBus;
    timetableState.loadedAt = Date.now();
  } catch (err) {
    console.warn("Timetable sync failed:", err && err.message ? err.message : err);
  } finally {
    timetableLoadInProgress = false;
  }
}

function getActiveTimetableState(busRegNo, now) {
  const schedules = timetableState.schedulesByBus.get(busRegNo);
  if (!schedules || schedules.length === 0) return null;

  const nowDay = now.getDay();
  const nowMin = now.getHours() * 60 + now.getMinutes() + now.getSeconds() / 60;
  let best = null;

  for (const s of schedules) {
    const dep = s.departureMin;
    const arr = s.arrivalMin;

    if (arr > dep) {
      if (s.dayOfWeek !== nowDay) continue;
      if (nowMin < dep || nowMin > arr) continue;

      const elapsedMin = nowMin - dep;
      const durationMin = arr - dep;
      if (!best || dep > best.depRank) {
        best = {
          ...s,
          elapsedMin,
          durationMin,
          depRank: dep,
        };
      }
      continue;
    }

    const overnightArr = arr + 1440;
    const nextDay = (s.dayOfWeek + 1) % 7;

    if (s.dayOfWeek === nowDay && nowMin >= dep) {
      const elapsedMin = nowMin - dep;
      const durationMin = overnightArr - dep;
      if (!best || dep > best.depRank) {
        best = {
          ...s,
          elapsedMin,
          durationMin,
          depRank: dep,
        };
      }
      continue;
    }

    if (nextDay === nowDay && nowMin <= arr) {
      const elapsedMin = nowMin + 1440 - dep;
      const durationMin = overnightArr - dep;
      const depRank = dep - 1440;
      if (!best || depRank > best.depRank) {
        best = {
          ...s,
          elapsedMin,
          durationMin,
          depRank,
        };
      }
    }
  }

  if (!best) return null;

  const durationMin = Math.max(1, best.durationMin);
  const initialWaitMin = Math.min(WAIT_STOP_MINUTES, Math.max(0, durationMin - 1));
  const remainingAfterInitial = Math.max(0, durationMin - initialWaitMin);
  const finalWaitMin = Math.min(WAIT_STOP_MINUTES, Math.max(0, remainingAfterInitial - 1));
  const driveMinutes = Math.max(1, durationMin - initialWaitMin - finalWaitMin);
  const elapsedMin = Math.max(0, Math.min(durationMin, best.elapsedMin));

  if (elapsedMin <= initialWaitMin) {
    return {
      routeNo: best.routeNo,
      waiting: true,
      waitAtEnd: false,
      progress: 0,
      driveMinutes,
    };
  }

  if (elapsedMin >= initialWaitMin + driveMinutes) {
    return {
      routeNo: best.routeNo,
      waiting: true,
      waitAtEnd: true,
      progress: 1,
      driveMinutes,
    };
  }

  const progress = (elapsedMin - initialWaitMin) / driveMinutes;
  return {
    routeNo: best.routeNo,
    waiting: false,
    waitAtEnd: false,
    progress: Math.max(0, Math.min(1, progress)),
    driveMinutes,
  };
}

function parkBusOnPrimaryRoute(bus, nowIso) {
  const routeNo =
    timetableState.primaryRouteByBus.get(bus.busRegNo) ||
    BUS_ROUTE[bus.busRegNo] ||
    bus.routeNo;
  const route = ROUTES[routeNo];
  if (!route || !route.polyline || route.polyline.length < 2) return false;

  bus.routeNo = routeNo;
  const parked = jitter(route.polyline[0]);
  bus.lat = parked.lat;
  bus.lng = parked.lng;
  bus.speedKmh = 0;
  bus.heading = bearingDeg(route.polyline[0], route.polyline[1]);
  bus.updatedAt = nowIso;
  return true;
}

// -----------------------------------------------------------------------------
// Bus state
// -----------------------------------------------------------------------------
function makeBus(busId, idx) {
  const routeKeys = Object.keys(ROUTES);
  const routeNo =
    timetableState.primaryRouteByBus.get(busId) ||
    BUS_ROUTE[busId] ||
    routeKeys[idx % routeKeys.length] ||
    "120";
  const route = ROUTES[routeNo];
  const poly = route.polyline;

  // Spread buses across the route
  const segCount = Math.max(1, poly.length - 1);
  const segIndex = idx % segCount;
  const t = (idx % 10) / 10;

  const p = interpPoint(poly[segIndex], poly[segIndex + 1], t);
  const heading = bearingDeg(poly[segIndex], poly[segIndex + 1]);

  return {
    busId,
    busRegNo: busId,
    regNo: busId,
    operatorType: PRIVATE_BUS_IDS.has(busId) ? "Private" : "SLTB",
    routeNo,
    // internal movement state
    _segIndex: segIndex,
    _t: t,
    // live fields
    lat: p.lat,
    lng: p.lng,
    speedKmh: 25 + Math.random() * 25, // 25–50
    heading,
    updatedAt: new Date().toISOString(),
  };
}

const buses = BUS_IDS.map((id, i) => makeBus(id, i));

function applyPrimaryRoutesToBuses() {
  const nowIso = new Date().toISOString();
  for (const bus of buses) {
    if (!timetableState.primaryRouteByBus.has(bus.busRegNo)) continue;
    parkBusOnPrimaryRoute(bus, nowIso);
  }
}

async function syncTimetableState() {
  await refreshTimetablesFromDb();
  applyPrimaryRoutesToBuses();
}

syncTimetableState().catch(() => {});
setInterval(() => {
  syncTimetableState().catch(() => {});
}, TIMETABLE_REFRESH_MS);

// Move each bus along its route every tick
function tick() {
  const dt = TICK_MS / 1000;
  const now = new Date();
  const nowIso = now.toISOString();

  for (const b of buses) {
    const liveFromTimetable = getActiveTimetableState(b.busRegNo, now);
    if (liveFromTimetable && ROUTES[liveFromTimetable.routeNo]) {
      b.routeNo = liveFromTimetable.routeNo;
      const poly = ROUTES[b.routeNo].polyline;

      if (liveFromTimetable.waiting) {
        const waitPoint = liveFromTimetable.waitAtEnd ? poly[poly.length - 1] : poly[0];
        const headingFrom = liveFromTimetable.waitAtEnd ? poly[Math.max(0, poly.length - 2)] : poly[0];
        const headingTo = liveFromTimetable.waitAtEnd ? poly[poly.length - 1] : poly[1];
        const parked = jitter(waitPoint);

        b.lat = parked.lat;
        b.lng = parked.lng;
        b.speedKmh = 0;
        b.heading = bearingDeg(headingFrom, headingTo);
        b.updatedAt = nowIso;
        continue;
      }

      const routePoint = pointAlongRoute(b.routeNo, liveFromTimetable.progress);
      const moved = jitter(routePoint.point);
      const routeKm = routePoint.totalMeters / 1000;
      const baseSpeedKmh = routeKm / Math.max(0.1, liveFromTimetable.driveMinutes / 60);

      b.lat = moved.lat;
      b.lng = moved.lng;
      b.speedKmh = Math.max(8, Math.min(65, baseSpeedKmh + (Math.random() - 0.5) * 4));
      b.heading = routePoint.heading;
      b.updatedAt = nowIso;
      continue;
    }

    if (timetableState.primaryRouteByBus.has(b.busRegNo)) {
      parkBusOnPrimaryRoute(b, nowIso);
      continue;
    }

    const route = ROUTES[b.routeNo] || ROUTES["120"];
    const poly = route.polyline;

    if (!poly || poly.length < 2) continue;

    // random speed variation
    b.speedKmh = Math.max(
      8,
      Math.min(65, b.speedKmh + (Math.random() - 0.5) * 4)
    );

    let remaining = (b.speedKmh * 1000) / 3600 * dt; // meters to move this tick

    while (remaining > 0) {
      const i = b._segIndex;
      const a = poly[i];
      const c = poly[i + 1] || poly[0];

      // if at end, loop back to start
      if (!poly[i + 1]) {
        b._segIndex = 0;
        b._t = 0;
        continue;
      }

      const segLen = Math.max(1, haversineMeters(a, c));
      const segRemaining = segLen * (1 - b._t);

      if (remaining < segRemaining) {
        b._t += remaining / segLen;
        remaining = 0;
      } else {
        remaining -= segRemaining;
        b._segIndex = (b._segIndex + 1) % (poly.length - 1);
        b._t = 0;
      }
    }

    const a = poly[b._segIndex];
    const c = poly[b._segIndex + 1] || poly[0];
    const p = interpPoint(a, c, b._t);
    const pj = jitter(p);

    b.lat = pj.lat;
    b.lng = pj.lng;
    b.heading = bearingDeg(a, c);
    b.updatedAt = nowIso;
  }
}

setInterval(tick, TICK_MS);

// -----------------------------------------------------------------------------
// API
// -----------------------------------------------------------------------------
app.get("/health", (req, res) => {
  res.json({
    ok: true,
    service: "busdemoapi",
    tickMs: TICK_MS,
    busCount: buses.length,
    time: new Date().toISOString(),
  });
});

app.get("/api/routes", (req, res) => {
  const out = Object.entries(ROUTES).map(([routeNo, r]) => ({
    routeNo,
    name: r.name,
    polyline: r.polyline,
  }));
  res.setHeader("Content-Type", "application/json");
  res.send(JSON.stringify(out, null, 2));
});

app.get("/api/buses/lives", (req, res) => {
  const { routeNo } = req.query;

  const out = buses
    .filter((b) => (!routeNo ? true : String(b.routeNo) === String(routeNo)))
    .map((b) => ({
      busId: b.busId,
      busRegNo: b.busRegNo,
      operatorType: b.operatorType,
      routeNo: b.routeNo,
      lat: b.lat,
      lng: b.lng,
      speedKmh: Math.round(b.speedKmh * 10) / 10,
      heading: Math.round(b.heading),
      updatedAt: b.updatedAt,
    }));

  res.setHeader("Content-Type", "application/json");
  res.send(JSON.stringify(out, null, 2));
});

app.listen(PORT, "0.0.0.0", () => {
  console.log("NexBus demo API running on port " + PORT);
  console.log("   Health:  http://127.0.0.1:" + PORT + "/health");
  console.log("   Live:    http://127.0.0.1:" + PORT + "/api/buses/lives");
});

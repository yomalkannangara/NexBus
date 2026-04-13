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
const WAIT_STOP_MINUTES = Number(process.env.WAIT_STOP_MINUTES || 10);
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
// Helpers (movement + timetable)
// -----------------------------------------------------------------------------
const toRad = (deg) => (deg * Math.PI) / 180;
const toDeg = (rad) => (rad * 180) / Math.PI;
const DAY_SECONDS = 24 * 60 * 60;
const ROUTE_KEYS = Object.keys(ROUTES);

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
  const dLat = (meters * Math.cos(angle)) / 111320;
  const dLng = (meters * Math.sin(angle)) / (111320 * Math.cos(toRad(p.lat)));
  return { lat: p.lat + dLat, lng: p.lng + dLng };
}

function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value));
}

function hashString(input) {
  let hash = 0;
  for (let i = 0; i < input.length; i += 1) {
    hash = (hash * 31 + input.charCodeAt(i)) | 0;
  }
  return Math.abs(hash);
}

function parseTimeToSec(value) {
  if (!value || typeof value !== "string") return null;
  const parts = value.split(":").map((n) => Number(n));
  if (parts.length < 2 || parts.some((n) => Number.isNaN(n))) return null;
  const hh = parts[0] % 24;
  const mm = parts[1] % 60;
  const ss = (parts[2] || 0) % 60;
  return hh * 3600 + mm * 60 + ss;
}

function normalizeTripDurationSec(depSec, arrSec) {
  if (arrSec >= depSec) return arrSec - depSec;
  return DAY_SECONDS - depSec + arrSec;
}

function resolveShapeRouteKey(routeNo, busId) {
  if (ROUTES[routeNo]) return routeNo;
  if (ROUTE_KEYS.length === 0) return "120";
  const idx = hashString(`${routeNo}:${busId}`) % ROUTE_KEYS.length;
  return ROUTE_KEYS[idx];
}

function buildRouteGeometry(polyline) {
  const segmentLengths = [];
  const cumulative = [0];
  let total = 0;

  for (let i = 0; i < polyline.length - 1; i += 1) {
    const len = Math.max(1, haversineMeters(polyline[i], polyline[i + 1]));
    segmentLengths.push(len);
    total += len;
    cumulative.push(total);
  }

  return { segmentLengths, cumulative, total: Math.max(1, total) };
}

const ROUTE_GEOMETRY = Object.fromEntries(
  Object.entries(ROUTES).map(([key, route]) => [key, buildRouteGeometry(route.polyline)])
);

function pointOnRoute(routeNo, busId, progress01) {
  const shapeKey = resolveShapeRouteKey(routeNo, busId);
  const route = ROUTES[shapeKey] || ROUTES["120"];
  const geo = ROUTE_GEOMETRY[shapeKey] || ROUTE_GEOMETRY["120"];
  const poly = route.polyline;

  if (!poly || poly.length < 2) {
    return { lat: 6.9378, lng: 79.8437, heading: 0, totalMeters: 1 };
  }

  const progress = clamp(progress01, 0, 1);
  const targetDist = progress * geo.total;
  let segIdx = geo.segmentLengths.length - 1;

  for (let i = 0; i < geo.segmentLengths.length; i += 1) {
    if (targetDist <= geo.cumulative[i + 1]) {
      segIdx = i;
      break;
    }
  }

  const segStartDist = geo.cumulative[segIdx];
  const segLen = geo.segmentLengths[segIdx] || 1;
  const t = clamp((targetDist - segStartDist) / segLen, 0, 1);

  const a = poly[segIdx];
  const b = poly[segIdx + 1] || poly[segIdx];
  const p = interpPoint(a, b, t);

  return {
    lat: p.lat,
    lng: p.lng,
    heading: bearingDeg(a, b),
    totalMeters: geo.total,
  };
}

function secOfDay(date) {
  return date.getHours() * 3600 + date.getMinutes() * 60 + date.getSeconds();
}

function isActiveTripNow(nowSec, depSec, arrSec, carryFromPreviousDay) {
  if (arrSec >= depSec) {
    return nowSec >= depSec && nowSec <= arrSec;
  }
  if (carryFromPreviousDay) {
    return nowSec <= arrSec;
  }
  return nowSec >= depSec;
}

function elapsedTripSec(nowSec, depSec, arrSec, carryFromPreviousDay) {
  if (arrSec >= depSec) {
    return clamp(nowSec - depSec, 0, normalizeTripDurationSec(depSec, arrSec));
  }
  if (carryFromPreviousDay) {
    return (DAY_SECONDS - depSec) + nowSec;
  }
  return clamp(nowSec - depSec, 0, normalizeTripDurationSec(depSec, arrSec));
}

function cloneCache(cache) {
  const out = new Map();
  for (const [busId, trips] of cache.entries()) {
    out.set(busId, trips.map((t) => ({ ...t })));
  }
  return out;
}

function sortTrips(trips) {
  trips.sort((a, b) => {
    if (a.day !== b.day) return a.day - b.day;
    if (a.depSec !== b.depSec) return a.depSec - b.depSec;
    return a.arrSec - b.arrSec;
  });
  return trips;
}

function buildFallbackTimetableCache() {
  const templates = [
    ["05:30:00", "06:30:00"],
    ["07:30:00", "08:30:00"],
    ["10:00:00", "11:00:00"],
    ["13:00:00", "14:00:00"],
    ["16:00:00", "17:00:00"],
    ["19:00:00", "20:00:00"],
  ];

  const map = new Map();
  for (const busId of BUS_IDS) {
    const routeNo = BUS_ROUTE[busId] || "120";
    const trips = [];
    for (let day = 0; day <= 6; day += 1) {
      for (const [dep, arr] of templates) {
        const depSec = parseTimeToSec(dep);
        const arrSec = parseTimeToSec(arr);
        if (depSec === null || arrSec === null) continue;
        trips.push({ day, depSec, arrSec, routeNo });
      }
    }
    map.set(busId, sortTrips(trips));
  }
  return map;
}

let dbPool = null;
let timetableSource = "fallback";
const fallbackTimetableCache = buildFallbackTimetableCache();
let timetableCache = cloneCache(fallbackTimetableCache);

async function ensureDbPool() {
  if (!mysql) return null;
  if (!dbPool) {
    dbPool = mysql.createPool(DB_CONFIG);
  }
  return dbPool;
}

function normalizeDbRows(rows) {
  const grouped = new Map();

  for (const row of rows) {
    const busId = String(row.bus_reg_no || "").trim();
    if (!busId) continue;

    const day = Number(row.day_of_week);
    if (!Number.isInteger(day) || day < 0 || day > 6) continue;

    const depSec = parseTimeToSec(String(row.departure_time || ""));
    const arrSec = parseTimeToSec(String(row.arrival_time || ""));
    if (depSec === null || arrSec === null) continue;

    const routeNo = String(row.route_no || BUS_ROUTE[busId] || "120").trim() || "120";
    if (!grouped.has(busId)) grouped.set(busId, []);
    grouped.get(busId).push({ day, depSec, arrSec, routeNo });
  }

  const merged = cloneCache(fallbackTimetableCache);
  for (const [busId, trips] of grouped.entries()) {
    merged.set(busId, sortTrips(trips));
  }

  return merged;
}

async function refreshTimetableCache() {
  if (!mysql) {
    timetableSource = "fallback-no-mysql2";
    timetableCache = cloneCache(fallbackTimetableCache);
    return;
  }

  try {
    const pool = await ensureDbPool();
    if (!pool) {
      timetableSource = "fallback-no-db-pool";
      timetableCache = cloneCache(fallbackTimetableCache);
      return;
    }

    const [rows] = await pool.query(
      `SELECT
         t.bus_reg_no,
         t.day_of_week,
         TIME_FORMAT(t.departure_time, '%H:%i:%s') AS departure_time,
         TIME_FORMAT(t.arrival_time, '%H:%i:%s') AS arrival_time,
         CAST(r.route_no AS CHAR) AS route_no
       FROM timetables t
       LEFT JOIN routes r ON r.route_id = t.route_id
       WHERE t.bus_reg_no IS NOT NULL
         AND t.departure_time IS NOT NULL
         AND t.arrival_time IS NOT NULL
         AND (t.effective_from IS NULL OR t.effective_from <= CURDATE())
         AND (t.effective_to   IS NULL OR t.effective_to   >= CURDATE())`
    );

    if (!Array.isArray(rows) || rows.length === 0) {
      timetableSource = "fallback-empty-db";
      timetableCache = cloneCache(fallbackTimetableCache);
      return;
    }

    timetableCache = normalizeDbRows(rows);
    timetableSource = "database";
  } catch (err) {
    timetableSource = "fallback-db-error";
    timetableCache = cloneCache(fallbackTimetableCache);
    console.warn("[busdemoapi] Timetable DB load failed, using fallback:", err.message);
  }
}

function tripsForBusOnDay(busId, day) {
  const trips = timetableCache.get(busId) || [];
  const today = [];
  const prevDayOvernight = [];
  const prevDay = (day + 6) % 7;

  for (const trip of trips) {
    if (trip.day === day) {
      today.push(trip);
    } else if (trip.day === prevDay && trip.depSec > trip.arrSec) {
      prevDayOvernight.push(trip);
    }
  }

  return { today, prevDayOvernight };
}

function findActiveTrip(nowSec, todayTrips, prevDayOvernightTrips) {
  for (const trip of todayTrips) {
    if (isActiveTripNow(nowSec, trip.depSec, trip.arrSec, false)) {
      return { trip, carryFromPreviousDay: false };
    }
  }

  for (const trip of prevDayOvernightTrips) {
    if (isActiveTripNow(nowSec, trip.depSec, trip.arrSec, true)) {
      return { trip, carryFromPreviousDay: true };
    }
  }

  return null;
}

function findNextTrip(nowSec, todayTrips) {
  for (const trip of todayTrips) {
    if (trip.depSec >= nowSec) {
      return trip;
    }
  }
  return todayTrips.length > 0 ? todayTrips[0] : null;
}

function findLastCompletedTrip(nowSec, todayTrips) {
  let latest = null;
  for (const trip of todayTrips) {
    const duration = normalizeTripDurationSec(trip.depSec, trip.arrSec);
    if (trip.depSec > trip.arrSec) {
      continue;
    }
    if (nowSec >= trip.arrSec && duration > 0) {
      latest = trip;
    }
  }
  return latest;
}

// -----------------------------------------------------------------------------
// Bus state
// -----------------------------------------------------------------------------
function makeBus(busId, idx) {
  const routeNo = BUS_ROUTE[busId] || ROUTE_KEYS[idx % ROUTE_KEYS.length] || "120";
  const seedPoint = pointOnRoute(routeNo, busId, (idx % 10) / 10);

  return {
    busId,
    busRegNo: busId,
    regNo: busId,
    operatorType: PRIVATE_BUS_IDS.has(busId) ? "Private" : "SLTB",
    routeNo,
    lat: seedPoint.lat,
    lng: seedPoint.lng,
    speedKmh: 0,
    heading: seedPoint.heading,
    updatedAt: new Date().toISOString(),
  };
}

const buses = BUS_IDS.map((id, i) => makeBus(id, i));

function updateBusFromTimetable(bus, now) {
  const nowSec = secOfDay(now);
  const day = now.getDay();
  const { today, prevDayOvernight } = tripsForBusOnDay(bus.busId, day);

  const active = findActiveTrip(nowSec, today, prevDayOvernight);

  if (active) {
    const trip = active.trip;
    bus.routeNo = trip.routeNo;

    const durationSec = Math.max(60, normalizeTripDurationSec(trip.depSec, trip.arrSec));
    const elapsedSec = elapsedTripSec(nowSec, trip.depSec, trip.arrSec, active.carryFromPreviousDay);

    const minDwellSec = WAIT_STOP_MINUTES * 60;
    const dwellSec = durationSec >= minDwellSec ? minDwellSec : Math.floor(durationSec * 0.5);

    if (elapsedSec <= dwellSec) {
      const p = pointOnRoute(bus.routeNo, bus.busId, 0);
      const pj = jitter({ lat: p.lat, lng: p.lng });
      bus.lat = pj.lat;
      bus.lng = pj.lng;
      bus.heading = p.heading;
      bus.speedKmh = 0;
      bus.updatedAt = now.toISOString();
      return;
    }

    const movingSec = Math.max(1, durationSec - dwellSec);
    const movingElapsedSec = clamp(elapsedSec - dwellSec, 0, movingSec);
    const progress = movingElapsedSec / movingSec;
    const p = pointOnRoute(bus.routeNo, bus.busId, progress);
    const pj = jitter({ lat: p.lat, lng: p.lng });

    const plannedSpeed = ((p.totalMeters / 1000) / (movingSec / 3600));
    const randomizedSpeed = plannedSpeed * (0.88 + Math.random() * 0.24);

    bus.lat = pj.lat;
    bus.lng = pj.lng;
    bus.heading = p.heading;
    bus.speedKmh = clamp(randomizedSpeed, 8, 65);
    bus.updatedAt = now.toISOString();
    return;
  }

  const nextTrip = findNextTrip(nowSec, today);
  if (nextTrip) {
    bus.routeNo = nextTrip.routeNo;
    const p = pointOnRoute(bus.routeNo, bus.busId, 0);
    const pj = jitter({ lat: p.lat, lng: p.lng });
    bus.lat = pj.lat;
    bus.lng = pj.lng;
    bus.heading = p.heading;
    bus.speedKmh = 0;
    bus.updatedAt = now.toISOString();
    return;
  }

  const lastTrip = findLastCompletedTrip(nowSec, today);
  if (lastTrip) {
    bus.routeNo = lastTrip.routeNo;
    const p = pointOnRoute(bus.routeNo, bus.busId, 1);
    const pj = jitter({ lat: p.lat, lng: p.lng });
    bus.lat = pj.lat;
    bus.lng = pj.lng;
    bus.heading = p.heading;
    bus.speedKmh = 0;
    bus.updatedAt = now.toISOString();
    return;
  }

  bus.routeNo = BUS_ROUTE[bus.busId] || bus.routeNo || "120";
  const p = pointOnRoute(bus.routeNo, bus.busId, 0);
  const pj = jitter({ lat: p.lat, lng: p.lng });
  bus.lat = pj.lat;
  bus.lng = pj.lng;
  bus.heading = p.heading;
  bus.speedKmh = 0;
  bus.updatedAt = now.toISOString();
}

function tick() {
  const now = new Date();
  for (const bus of buses) {
    updateBusFromTimetable(bus, now);
  }
}

// -----------------------------------------------------------------------------
// API
// -----------------------------------------------------------------------------
app.get("/health", (req, res) => {
  res.json({
    ok: true,
    service: "busdemoapi",
    tickMs: TICK_MS,
    waitStopMinutes: WAIT_STOP_MINUTES,
    timetableSource,
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

async function start() {
  await refreshTimetableCache();

  setInterval(() => {
    refreshTimetableCache().catch((err) => {
      console.warn("[busdemoapi] Timetable refresh failed:", err.message);
    });
  }, TIMETABLE_REFRESH_MS);

  tick();
  setInterval(tick, TICK_MS);

  app.listen(PORT, "0.0.0.0", () => {
    console.log("NexBus demo API running on port " + PORT);
    console.log("   Health:  http://127.0.0.1:" + PORT + "/health");
    console.log("   Live:    http://127.0.0.1:" + PORT + "/api/buses/lives");
    console.log("   Timetable source: " + timetableSource);
    console.log("   Planned zero-speed dwell: " + WAIT_STOP_MINUTES + " minutes");
  });
}

start().catch((err) => {
  console.error("[busdemoapi] Fatal startup error:", err);
  process.exit(1);
});

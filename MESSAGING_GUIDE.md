# NexBus Messaging System Guide

## Overview

The NexBus Messaging system provides real-time, depot-wide communication between staff, with scope-based recipient targeting, quick action buttons, and live notifications via Server-Sent Events (SSE).

## Features

### 1. **Inbox & Compose**
- **Real-time inbox** with auto-updating message count badges
- **Scope-based sending**: Send to individuals, by role, by bus, or by route
- **Message templates** for common alerts (delays, breakdowns, maintenance, etc.)
- **Priority levels**: Normal, Urgent, Critical
- **Search & filters**: Filter by unread, alerts, or messages

### 2. **Quick Actions**
- **Acknowledge**: Mark message as acknowledged (metadata tracked)
- **Escalate**: Flag message for manager review (metadata tracked)
- **Archive**: Hide message from inbox (can be restored)

### 3. **Real-time Delivery**
- **Server-Sent Events (SSE)** push notifications
- **Auto-reconnect** on connection loss (3-second retry)
- **5-minute connection timeout** with graceful close

### 4. **Recipient Expansion**
Sending by scope automatically expands recipients:
- **Individual**: Direct user IDs
- **Role**: All users with selected role(s) in depot
- **Bus**: All drivers/conductors assigned to selected bus(es)
- **Route**: All drivers/conductors assigned to selected route(s)
- **All Depot**: Every staff member in the depot

### 5. **Notification Badges**
- Unread count badge on stat row
- Visual indicators for alert-type messages
- Dynamic updates when new messages arrive

## Database Schema

### Main Tables

#### `notifications`
Stores all messages/notifications for users.

```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'Message',         -- Message, Delay, Timetable, Alert, Breakdown
    message TEXT,
    is_seen TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metadata JSON,                              -- {acknowledged_by, escalated, archived, etc}
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

**Metadata fields**:
- `acknowledged_by` (int): User ID of who acknowledged
- `acknowledged_at` (timestamp): When acknowledged
- `escalated` (bool): Whether escalated
- `escalated_by` (int): User ID of who escalated
- `escalated_at` (timestamp): When escalated
- `archived` (bool): Whether archived
- `archived_by` (int): User ID of who archived
- `archived_at` (timestamp): When archived

### Related Tables
- `users`: Contains user info (id, first_name, last_name, role, sltb_depot_id)
- `sltb_assignments`: Maps drivers/conductors to buses and routes
- `sltb_buses`: Bus info (bus_id, bus_registration_no, sltb_depot_id)
- `sltb_routes`: Route info (route_id, route_name, sltb_depot_id)

## API Endpoints

### GET `/O/messages`
View inbox with optional filters.

**Query Parameters**:
- `filter` (all|unread|alert|message): Filter type [default: all]
- `msg` (sent|error): Flash message

**Scope Tabs**:
- Individual: Single user selection
- By Role: Multi-role selection
- By Bus: Multi-bus selection
- By Route: Multi-route selection
- All Depot: Send to every staff member

### POST `/O/messages`
Send a new message.

**Form Data**:
- `action` = "send"
- `scope` (individual|role|depot|bus|route)
- `to[]` (int): Recipient user IDs or role names
- `message` (string): Message text [max 500 chars]
- `priority` (normal|urgent|critical)
- `all_depot` (0|1): Override recipients and send to all

**Response**: Redirect to `/O/messages?msg=sent` or `msg=error`

### GET `/O/messages?action=read&id=123`
Mark message as read (silent AJAX).

**Response**: HTTP 204 (No Content)

### GET `/O/messages?action=ack&id=123`
Acknowledge a message.

**Response**: JSON `{status: "ok"}`

### GET `/O/messages?action=escalate&id=123`
Escalate a message to manager.

**Response**: JSON `{status: "ok"}`

### GET `/O/messages?action=archive&id=123`
Archive a message.

**Response**: JSON `{status: "ok"}`

### GET `/O/messages/stream`
Server-Sent Events stream for real-time messages.

**Query Parameters**:
- `last_id` (int): Resume from last known message ID

**Event Format**:
```json
{
    "id": 123,
    "type": "Message|Delay|Breakdown|Alert|Timetable",
    "message": "Message text",
    "from": "John Doe",
    "created_at": "2026-02-22 14:30:00",
    "priority": "normal|urgent|critical"
}
```

**Connection**: Persistent (5 minutes max)

## Code Structure

### Models

#### `MessageModel` (`models/depot_officer/MessageModel.php`)
Core messaging logic:
- `expandRecipients()`: Expand recipients by scope
- `send()`: Send message with scope expansion
- `recent()`: Fetch recent messages with sender names
- `markRead()`: Mark message as read
- `acknowledge()`: Mark as acknowledged
- `escalate()`: Mark as escalated
- `archive()`: Mark as archived

#### `DepotOfficerModel` (`models/depot_officer/DepotOfficerModel.php`)
Facade for depot officer functions:
- `sendMessage()`: Delegate to MessageModel::send()
- `recentMessages()`: Delegate to MessageModel::recent()
- `availableRoles()`: Get distinct roles in depot
- `depotBusesForMessaging()`: Get buses for targeting
- `depotRoutesForMessaging()`: Get routes for targeting

### Controllers

#### `DepotOfficerController::messages()`
Main message handler:
- GET: Render inbox with filters
- POST (action=send): Send message
- GET (action=read): Mark read
- GET (action=ack): Acknowledge
- GET (action=escalate): Escalate
- GET (action=archive): Archive

#### `DepotOfficerController::sseStream()`
Server-Sent Events endpoint:
- Long-polling for new messages
- 5-minute connection timeout
- Auto-reconnect on client close

### Views

#### `views/depot_officer/messages.php`
Single-page messaging interface:
- **Left panel**: Inbox with filters and search
- **Center panel**: Message thread viewer
- **Right panel**: Compose form with scope selection
- **Top bar**: Refresh, new message buttons
- **Stats row**: Total, unread, alerts, staff counts
- **JavaScript**: SSE client, filters, quick actions

## Migration Instructions

### 1. Ensure tables exist
```bash
# Run existing migrations
cd /path/to/NexBus-1
php scripts/run_sql.php < database/migrations/127_0_0_1\ \(9\).sql
```

### 2. Verify `notifications` table structure
```sql
-- Check if metadata column exists
DESCRIBE notifications;

-- If not, add it:
ALTER TABLE notifications ADD COLUMN metadata JSON AFTER is_seen;
```

### 3. Test the system
1. Login as Depot Officer
2. Navigate to `/O/messages`
3. Compose a test message
4. Select recipients and send
5. Check real-time updates via SSE

## Usage Examples

### Send to individual users
1. Click "New Message"
2. Select "Individual" tab
3. Check desired staff members
4. Type message
5. Click Send

### Send to all drivers
1. Click "New Message"
2. Select "By Role" tab
3. Check "Driver"
4. Type message
5. Click Send

### Send to all buses on Route 5
1. Click "New Message"
2. Select "By Route" tab
3. Check "Route 5"
4. Type message
5. Click Send

### Send urgent message to all depot staff
1. Click "New Message"
2. Select "All Depot" tab
3. Set priority to "Urgent"
4. Type message
5. Click Send

## Configuration

### Connection Timeout
SSE connection lasts **5 minutes** (300 seconds) before reconnecting.

### Polling Interval
Client polls for new messages **every 1 second** when SSE connected.

### Auto-reconnect Delay
Client waits **3 seconds** before retrying on connection loss.

### Message Retention
Messages remain in `notifications` table indefinitely (can be archived via UI).

## Troubleshooting

### Messages not appearing in real-time
- Check browser console for SSE errors
- Verify `/O/messages/stream` endpoint is accessible
- Check PHP `set_time_limit` is sufficient (300s recommended)

### Archive not working
- Ensure `notifications` table has `metadata` column
- Check user permissions (must be message recipient)
- Look for JavaScript errors in browser console

### Scope expansion not working
- Verify database tables exist: `sltb_assignments`, `sltb_buses`, `sltb_routes`
- Check user's `sltb_depot_id` is set correctly
- Ensure role names match exactly (case-sensitive)

### High database load
- Consider adding index on `notifications(user_id, is_seen, created_at)`
- Implement message archival/cleanup policy (not yet in MVP)
- Monitor SSE connection counts

## Future Enhancements

1. **Read Receipts**: Track who read each message
2. **Message Templates**: Save custom templates
3. **Message Search**: Full-text search across old messages
4. **Delivery Confirmation**: Require acknowledgement for critical messages
5. **Rate Limiting**: Prevent message spam
6. **Mobile Notifications**: Push to mobile apps
7. **Message Encryption**: End-to-end encrypted messages
8. **Scheduled Messages**: Send at specific times
9. **Message Replies**: Thread-based conversations
10. **Voice/Video**: Audio/video message support

## Support

For issues or questions:
1. Check this guide's troubleshooting section
2. Review browser console for errors
3. Check PHP error logs
4. Review database schema integrity

---

**Last Updated**: February 22, 2026  
**Version**: 1.0 (MVP)

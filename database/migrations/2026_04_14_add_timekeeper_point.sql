-- SLTB Timekeeper: designate whether a timekeeper is at the
-- starting depot (start) or the destination depot (end).
-- Default 'start' keeps all existing accounts working without re-login.
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `timekeeper_point`
    ENUM('start','end') NOT NULL DEFAULT 'start'
    COMMENT 'SLTBTimekeeper: start=originating depot, end=destination depot';

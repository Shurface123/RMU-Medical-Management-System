-- ═══════════════════════════════════════════════════════════
-- Phase 4: Cross-Dashboard Integration Migration
-- Expands notifications.type ENUM for nurse cross-dashboard events
-- ═══════════════════════════════════════════════════════════

-- Expand the type ENUM to include nurse cross-dashboard event types
ALTER TABLE `notifications`
  MODIFY COLUMN `type` VARCHAR(50) NOT NULL DEFAULT 'system';

-- Add an index for faster cross-dashboard lookups
ALTER TABLE `notifications`
  ADD INDEX IF NOT EXISTS `idx_notif_role_type` (`user_role`, `type`);

-- Add priority 'critical' to the priority ENUM
ALTER TABLE `notifications`
  MODIFY COLUMN `priority` VARCHAR(20) DEFAULT 'normal';

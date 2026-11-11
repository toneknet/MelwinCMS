-- Existing installations: run once against the site's database.
-- Replace m_user with the actual user table name (configured dbPrefix + user).
-- Check first; skip ALTER TABLE if lostpasswordTime already exists:
-- SHOW COLUMNS FROM `m_user` LIKE 'lostpasswordTime';
ALTER TABLE `m_user`
  ADD COLUMN `lostpasswordTime` datetime NULL DEFAULT NULL AFTER `lostpassword`;

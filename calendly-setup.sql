-- Insert Calendly settings into database
INSERT INTO wp_tfsp_calendly_settings (setting_key, setting_value) VALUES
('api_token', 'eyJraWQiOiIxY2UxZTEzNjE3ZGNmNzY2YjNjZWJjY2Y4ZGM1YmFmYThhNjVlNjg0MDIzZjczMmJlODM0OWIyMzgwMTM1YjQiLCJ0eXAiOiJQQVQiLCJhbGciOiJFUzI1NiJ9.eyJpc3MiOiJodHRwczovL2F1dGguY2FsZW5kbHkuY29tIiwiaWF0IjoxNzYwNjE3NTU5LCJqdGkiOiI2NDRiOWVjZS0yOWQ3LTQ4NmUtOTM4ZS1hNmM1ZTQ4NzM4OTEiLCJ1c2VyX3V1aWQiOiI5YTVjZGE2YS1jYWI0LTRhNzEtYTIzZi03MjRlNDBjYWIzOGUifQ.I9K7RgdNClep5pzr2xD11v14HN0q3n9pCfBhHsrcFv2xI4-e4KxAiWnIIPZ4ajDYkcrI_38RNyOBr59mYzPRJQ'),
('event_type_uuid', '50dfc9ce-0765-41f0-992e-fc6b351fa270'),
('user_uri', 'https://api.calendly.com/users/9a5cda6a-cab4-4a71-a23f-724e40cab38e'),
('organization_uri', 'https://api.calendly.com/organizations/3f9ce3ca-e5bc-42a5-bbbf-5f56f90b7040'),
('webhook_signing_key', 'lK-fjZZM6jCqkD8kdZ-asdFhfnn3NzFbUK2TQ0FyXxY'),
('enabled', '1'),
('plan_type', 'basic')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

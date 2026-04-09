SHOW TABLES LIKE 'landing_%';
SHOW TABLES LIKE 'chatbot_%';
SHOW TABLES LIKE 'public_appointment_bookings';
SELECT COUNT(*) AS hero_rows      FROM landing_hero_content;
SELECT COUNT(*) AS stat_rows      FROM landing_statistics;
SELECT COUNT(*) AS service_rows   FROM landing_services;
SELECT COUNT(*) AS faq_rows       FROM landing_faq;
SELECT COUNT(*) AS config_rows    FROM landing_page_config;
SELECT COUNT(*) AS chatbot_rows   FROM chatbot_knowledge_base;

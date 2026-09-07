<?php
/**
 * Production credentials — copy to config.secret.php on the server.
 *
 * config.php reads in this order:
 *   environment variables (APPSGAIN_DB_PASS …)  →  this file  →  defaults
 *
 * Keep the real config.secret.php OFF version control. It is blocked
 * from web access by .htaccess, but that only helps if Apache is
 * honouring .htaccess — on Hostinger shared hosting it is.
 */
return [
    'db_host' => 'localhost',
    'db_port' => '3306',
    'db_name' => 'u000000000_yourdb',
    'db_user' => 'u000000000_youruser',
    'db_pass' => 'put-the-real-password-here',

    /* AI chatbot. Server-side only — api/chatbot.php reads it to call
       OpenAI and never returns it to the browser. Leave it out to run
       the site with the chatbot disabled. */
    'openai_api_key' => 'sk-...',
];

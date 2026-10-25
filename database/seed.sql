-- Seed data for CAINT EARN HUB
-- Prepopulates users, profiles, categories, vetted paying platforms, and platform settings

INSERT INTO users (id, role, email, username, password_hash, phone, status) VALUES
(1, 'admin', 'caintcreatin@gmail.com', 'caint_admin', '$2y$10$niyUIpSzdN06fwsqH7elb.C4WLe6jnX.gpGSiVg5WyU0BdD5EoPYW', '0714772901', 'active'),
(2, 'client', 'client@caintcreation.wuaze.com', 'client_peter', '$2y$10$U7Baqb21j/t4gETU62lZS.eIbtCCgfj9PsMYT2kqJhSvxERrfWoUW', '0756963430', 'active'),
(3, 'worker', 'worker@caintcreation.wuaze.com', 'worker_kevin', '$2y$10$gZRTJjyMQDQ6FOmg68yNnOOWWdHmJSto7R/tJ.uHEZ0RC26XmccaG', '0714772901', 'active')
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO user_profiles (user_id, display_name, headline, bio, skills, location, availability, hourly_rate, is_public, payout_method, payout_details) VALUES
(1, 'Caint Admin', 'Lead Quality & Platform Verification Officer', 'Dedicated to hand-testing every digital earning portal before opening it to Kenyan workers.', 'Platform Auditing, Payout Verification, Fraud Detection', 'Nairobi, Kenya', 'Full-time', 0.00, 1, 'M-Pesa', '0714772901'),
(2, 'Peter Mwangi', 'Digital Operations Coordinator', 'Recruiting verified Kenyan gig workers for high-volume content, testing, and micro-tasks.', 'Quality Assurance, Task Coordination, Community Management', 'Westlands, Nairobi', 'Flexible', 1500.00, 1, 'M-Pesa', '0756963430'),
(3, 'Kevin Ochieng', 'Freelance Task Specialist & AI Data Trainer', 'Active online worker completing verification, video reviews, and data annotation gigs.', 'Data Labeling, Video UGC, Audio Transcription, Prompt Evaluation', 'Eldoret, Kenya', 'Available now', 800.00, 1, 'M-Pesa', '0714772901')
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO categories (id, name, slug, description, icon_name, sort_order) VALUES
(1, 'General Gigs', 'general-gigs', 'Verified portals paying for standard online tasks and credential testing', 'check', 1),
(2, 'Video Recording', 'video-recording', 'User-generated short form clips, testimonial prompts, and English reading videos', 'video', 2),
(3, 'Data Entry & Audio', 'data-entry-audio', 'Audio transcription, Kenyan language phonetic labeling, and spreadsheet tasks', 'mic', 3),
(4, 'AI Training & Prompts', 'ai-training-prompts', 'Evaluating AI chat model responses, Swahili translation, and fact-checking', 'cpu', 4),
(5, 'App & Web Testing', 'app-web-testing', 'Functional testing of mobile apps, bug hunting, and usability surveys', 'smartphone', 5)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO platforms (id, slug, title, category_id, user_id, short_desc, full_desc, requirements, steps_guide, earning_potential, payout_method, payout_proofs, logo_image, task_image, work_type, token_price, access_url, verification_status, listing_status, views_count, unlocks_count) VALUES
(1, 'get-paid-to-create-emails', 'Get Paid to Create Verified Email Accounts', 1, 1, 
'Earn per registered email batch. Activate two-factor authentication to maximize payout eligibility. Payouts processed every 5 days following quality verification.',
'This hand-tested platform connects remote contributors with marketing automation clients needing bulk verified accounts for testing and newsletter deliverability. Every account must follow strict safety protocols and unique credentials. Hand-tested across 3 test cycles and confirmed steady weekly M-Pesa payouts.',
'Stable internet connection, Android or PC browser, ability to follow unique naming guidelines, and basic SMS verification familiarity.',
'Step 1: Open the verified direct onboarding link unlocked via this portal.\nStep 2: Sign up with your primary email address and confirm your account.\nStep 3: Access your worker dashboard and reserve your first batch of 10 email slots.\nStep 4: Follow the requested domain checklist and activate 2FA as specified.\nStep 5: Submit the credential sheet and track payout approval inside your portal.',
'KSh 600 - KSh 2,500 daily', 'Direct M-Pesa or PayPal',
'["proof_email_payout_1.png", "proof_email_payout_2.png"]',
'assets/images/logo.png', NULL, 'Remote', 50.00, 'https://workspace-hub.org/auth/onboarding?ref=caint_verified_119', 'verified', 'open', 342, 64),

(2, 'record-videos-ugc', 'Short-Form English Video Recording Prompts', 2, 1,
'Earn by recording 30 to 60 second talking-head video clips. You receive specific guided scripts (e.g. morning routine, product reviews) spoken clearly in English.',
'A legitimate creator pool platform paying contributors to record raw camera footage used for training video AI speech models and lifestyle brand creatives. No professional lighting or studio equipment is needed; an ordinary modern smartphone camera is sufficient. Verified payouts directly into M-Pesa.',
'Smartphone with at least 1080p camera, clear English speech without background music, quiet room, and natural lighting.',
'Step 1: Follow the unlocked registration link and create your contributor profile.\nStep 2: Complete the 45-second audio/video voice verification prompt.\nStep 3: Browse open prompts, choose a topic you feel comfortable with, and hit Record.\nStep 4: Upload the uncompressed file directly into the portal.\nStep 5: Receive payout confirmation within 48 hours directly to your phone.',
'KSh 800 - KSh 3,200 per video prompt', 'M-Pesa Express, Wise',
'["proof_video_payout_1.png", "proof_video_payout_2.png"]',
'assets/images/logo.png', NULL, 'Remote', 50.00, 'https://ugc-recorders.co/join?campaign=ke_ugc_2026', 'verified', 'open', 512, 118),

(3, 'kenyan-dialect-ai-annotation', 'Sheng & Swahili AI Model Evaluator', 4, 1,
'Compare two AI model responses and judge grammatical naturalness, cultural context, and accuracy for regional Kenyan languages.',
'Leading generative AI labs are fine-tuning African language models. Contributors read side-by-side completions to factual prompts and score which response is safer, more helpful, and fluent in Kenyan Swahili or Sheng. Completed benchmark tasks and received weekly bank and M-Pesa settlements.',
'Fluent understanding of written Kenyan English, standard Swahili, and common urban Sheng. Reliable laptop or tablet.',
'Step 1: Access the verified recruiter portal via the unlocked token link.\nStep 2: Complete the 20-minute language proficiency quiz.\nStep 3: Receive access to the worker queue with real-time task allocations.\nStep 4: Submit high quality ratings adhering to the provided rubric.\nStep 5: Weekly automated disbursements every Tuesday morning.',
'KSh 1,200 - KSh 4,800 per active hour', 'Direct Bank Transfer, M-Pesa',
'["proof_ai_eval_1.png"]',
'assets/images/logo.png', NULL, 'Remote', 50.00, 'https://data-evaluators.net/apply/kenya-swahili-specialist', 'verified', 'open', 429, 92)
ON DUPLICATE KEY UPDATE updated_at = NOW();

INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'CAINT EARN HUB'),
('site_tagline', 'Hand-tested online platforms and legit paying tasks in Kenya'),
('support_phone', '+254 714 772 901 / +254 756 963 430'),
('whatsapp_group_url', 'https://chat.whatsapp.com/GzJ1tjOfSEq5jWaX0oTQgE'),
('admin_email', 'caintcreation@gmail.com'),
('tiktok_url', 'https://www.tiktok.com/@caintcraetion?_r=1&_t=ZS-9Ap0hcO2YbP'),
('facebook_url', 'https://www.facebook.com/share/1KWiW3kgzY/'),
('telegram_url', 'https://t.me/Caintmanu'),
('x_url', 'https://x.com/Caintmanu'),
('snapchat_url', 'https://www.snapchat.com/add/caint56?share_id=fAwN2xjyEvQ&locale=en-US'),
('main_website_url', 'https://caintcreation.wuaze.com/'),
('default_token_price', '50.00'),
('paystack_public_key', ''),
('paystack_secret_key', ''),
('paystack_currency', 'KES')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

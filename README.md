# CAINT EARN HUB - Complete Documentation & Deployment Guide

Welcome to **CAINT EARN HUB**, a hand-tested, verified earning tasks and digital platform directory designed for Kenyan online workers, freelancers, and students.

---

## 1. Credentials & Configuration Summary

| Setting / Credential | Value |
|---|---|
| **Platform Name** | `CAINT EARN HUB` |
| **Support Phones** | `+254 714 772 901` / `+254 756 963 430` |
| **Support Email** | `caintcreation@gmail.com` |
| **Social Media** | TikTok (`@caintcraetion`), Facebook, Telegram (`@Caintmanu`), X (`@Caintmanu`), Snapchat (`@caint56`) |
| **Live Website** | `https://caintcreation.wuaze.com/` |
| **Admin Portal** | Private at `http://your-domain/admin/login.php` (Not linked anywhere on the public website) |
| **Admin Login** | Configure the administrator account securely after importing the database. |
| **Payment Gateway** | Paystack Direct Mobile Money (Safaricom M-Pesa STK Prompt without redirection) |

---

## 2. Key Business Rules Implemented

1. **Member vs. Creator Roles**: Regular visitors register as standard `worker` members. They cannot post work. Only the administrator can upgrade a user's role to `client` or `creator` inside **Admin -> Users & Roles** (`admin/users.php`).
2. **Post Task with Picture & Activation Amount**: Upgraded users and the admin can post a task, upload a banner image, specify the required activation fee (e.g. KSh 50.00), and share the link with workers via one-click copy or direct WhatsApp share.
3. **Transparent Maintenance Notice**: The statement *"The activation amount paid is a one-time fee specifically dedicated to enhance platform testing research and site maintenance"* is prominently displayed on the homepage, task cards, task detail page, unlock modal, and footer.
4. **Direct Safaricom Prompt**: When a user inputs their phone number, the system assumes it is a Safaricom number and triggers an instant prompt on their handset without redirecting them to an external Paystack hosted page.
5. **Mode of Payment Upload**: Members can choose their preferred payout method (Safaricom M-Pesa, Bank, PayPal, Airtel Money), enter their account details, and upload their payment proof or ID in their dashboard profile (`dashboard/profile.php`).
6. **No Categories / Clean Task Stream**: Category filters have been simplified into a clean, unified, fast keyword search.

---

## 3. Step-by-Step Testing Procedure (Local Environment)

Follow these steps to test every workflow locally right now before deploying to InfinityFree:

### Step 1: Open the Public Website
- Open your browser to: `http://localhost/webapp/`
- Check that your custom logo and favicon load correctly.
- Test searching for a task using the search bar (e.g., type `Video` or `Email`).

### Step 2: Test Direct Safaricom Activation Prompt
1. Click on any task (e.g. *Get Paid to Create Verified Email Accounts*).
2. Look at the sidebar card: notice the prominent notice explaining that the token funds research and site maintenance.
3. In the mobile number field, enter your phone number: `0714772901`.
4. Click **Unlock Verified Task · KSh 50.00**.
5. The modal will appear showing that the prompt has been dispatched.
6. Click **Verify PIN Confirmation & Unlock** (in local testing mode, this simulates instant approval without charging real money).
7. The verified onboarding link will appear immediately on the screen with a one-click **Copy** button.

### Step 3: Test Link Recovery
1. Go to `http://localhost/webapp/track.php`.
2. Enter the phone number `0714772901`.
3. Click **Search My Unlocked Tasks**.
4. All previously unlocked tasks will be retrieved instantly with their reference codes and setup instructions.

### Step 4: Test 24/7 Live Support Chat
1. Navigate to `http://localhost/webapp/support.php`.
2. Type a message as a visitor (e.g., *"How long do payouts take?"*).
3. In another tab or private window, log into `http://localhost/webapp/admin/login.php` with the administrator credentials configured for your environment.
4. In the admin menu, click **Live Support Desk**.
5. Open the visitor conversation and type a reply.
6. Switch back to `support.php` and notice the response appears in real time!

---

## 4. Step-by-Step Procedure to Upload & Host on InfinityFree

InfinityFree provides free PHP & MySQL hosting. Follow these exact steps to launch your website:

### Step A: Create an Account & Website on InfinityFree
1. Go to [infinityfree.com](https://infinityfree.com) and sign up for a free account.
2. In the Client Area, click **Create Account**.
3. Choose a domain name:
   - A free subdomain (e.g. `caintearn.infinityfreeapp.com` or `caintearn.kesug.com`), OR
   - The current live site is [`https://caintcreation.wuaze.com/`](https://caintcreation.wuaze.com/).
4. Click **Create Account** and wait 2 minutes for the account to become active.

### Step B: Create the MySQL Database on InfinityFree
1. Inside your InfinityFree dashboard, click **Manage** -> **Control Panel** (vPanel).
2. Find the **Databases** section and click **MySQL Databases**.
3. Under *Create a New Database*, enter a database name (e.g. `hub`) and click **Create Database**.
4. Note down the following details displayed on the page:
   - **MySQL Database Name**: (looks like `if0_XXXXXXXX_hub`)
   - **MySQL Username**: (looks like `if0_XXXXXXXX`)
   - **MySQL Hostname**: (looks like `sqlXXX.infinityfree.com` — **Do NOT use `localhost`**)
   - **MySQL Password**: (Your InfinityFree hosting account password from your client area)

### Step C: Import the Database Schema & Seeds
1. In the same vPanel, click **phpMyAdmin** next to your newly created database.
2. Inside phpMyAdmin, click the **Import** tab at the top.
3. Click **Choose File** and select `database/schema.sql` from your computer. Click **Go** at the bottom.
4. Next, click **Choose File** and select `database/seed.sql`. Click **Go** at the bottom.
5. All 12 tables and default tasks are now created!

### Step D: Update Database Connection Settings
Open `includes/db.php` on your computer before uploading, or edit it using InfinityFree's File Manager:

Find lines 29 to 32 in `includes/db.php`:
```php
defined('HOSTING_DB_HOST') or define('HOSTING_DB_HOST', 'sqlXXX.infinityfree.com'); // Put your InfinityFree MySQL Hostname here
defined('HOSTING_DB_NAME') or define('HOSTING_DB_NAME', 'if0_XXXXXXXX_hub');        // Put your InfinityFree Database Name here
defined('HOSTING_DB_USER') or define('HOSTING_DB_USER', 'if0_XXXXXXXX');            // Put your InfinityFree Database Username here
defined('HOSTING_DB_PASS') or define('HOSTING_DB_PASS', 'YOUR_INFINITYFREE_PASS');  // Put your InfinityFree Hosting Password here
```
*(Alternatively, you can edit the `.env` file if your FTP client allows uploading dotfiles).*

### Step E: Upload Files to InfinityFree `htdocs`
1. In your InfinityFree dashboard, click **File Manager** (or connect via an FTP client like FileZilla using the FTP details shown in your InfinityFree client area).
2. Open the **`htdocs/`** directory.
3. Delete any default file inside `htdocs/` (such as `default2.html`).
4. Upload all the files and folders from `c:\xampp\htdocs\webapp\` into `htdocs/`:
   ```
   htdocs/
   ├── admin/
   ├── api/
   ├── assets/
   ├── dashboard/
   ├── database/
   ├── includes/
   ├── uploads/
   ├── index.php
   ├── platforms.php
   ├── platform.php
   ├── track.php
   ├── support.php
   ├── login.php
   ├── register.php
   ├── favicon.ico
   └── ...
   ```
5. Ensure the `uploads/` folder and its subfolders (`uploads/tasks` and `uploads/proofs`) have write permissions (`755` or `777`).

---

## 5. How to Configure Paystack for Live Safaricom M-Pesa Direct Prompt

To receive real payments directly to your Paystack account without taking customers away from your site:

1. Sign up or log into [paystack.com](https://paystack.com) and ensure your account has **Kenya / KES** enabled.
2. In your Paystack Dashboard, go to **Settings -> API Keys & Webhooks**.
3. Copy your:
   - **Live Public Key** (`pk_live_...`)
   - **Live Secret Key** (`sk_live_...`)
4. On your live website, log in to the admin portal using the administrator credentials configured for your environment:
   - Go to: `https://caintcreation.wuaze.com/admin/login.php`
5. In the admin sidebar, click **Platform Settings**.
6. Under **Paystack Direct Mobile Money Settings**:
   - Paste your **Paystack Public Key**.
   - Paste your **Paystack Secret Key**.
7. Click **Save Configuration Changes**.

From that moment on:
- Whenever any customer inputs a Safaricom number (e.g. `0712345678`), the system calls Paystack's Charge API in the background.
- Safaricom immediately sends an **STK prompt directly to the customer's phone**.
- As soon as the customer enters their PIN on their phone, the task onboarding access unlocks automatically on their screen!
#   G i g _ m a k r t p l a c e _ w e b a p p l i c a t i o n  
 
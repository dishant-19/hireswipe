# HireSwipe

A Tinder-style job app. Job seekers swipe on companies, companies swipe on job seekers, and when both like each other it's a match and they can chat.

Built with plain PHP and MySQL, so it runs on XAMPP, WAMP, MAMP or any normal PHP hosting.

## Set it up in 4 steps

1. **Copy the folder.** Put the whole `hireswipe` folder inside `htdocs` (XAMPP) or `www` (WAMP).
2. **Create the database.** Open phpMyAdmin, click **Import**, choose `database.sql`, and press **Go**.
   This creates the `hireswipe` database, all the tables, and some demo people.
3. **Check `config.php`.** If your MySQL password is not empty, type it in `DB_PASS`.
4. **Open the site.** Go to `http://localhost/hireswipe/`

Needs PHP 8.0 or newer.

## Try it quickly

All demo accounts use the password `password123`.

| Who | Email |
| --- | --- |
| Job seeker | `seeker1@demo.com` (Aarav, already matched with TechNova) |
| Job seeker | `seeker2@demo.com` (Diya: swipe right on GreenLeaf Foods to get an instant match) |
| Company | `employer1@demo.com` (TechNova) |
| Company | `employer2@demo.com` (GreenLeaf Foods) |

Tip: open two different browsers (or one normal and one private window), log in as a job seeker in one and a company in the other, and chat in real time.

## What's included

- Sign up with name, email, mobile, password and confirm password
- Log in with email and password, or with a mobile number and a one-time code (OTP)
- Forgot password and reset
- Choose your side after signing up: job seeker or employer
- Profile setup with full checks (job seeker: age, location, nationality, qualification, experience, expected salary, employment type, industries, picture, CV. Company: name, location, industry, position, employment type, salary, job details, logo)
- Swipe cards: drag, tap the buttons, or use the left/right arrow keys. Best-fitting profiles come first, with a "% fit" score
- Match pop-up when both sides like each other
- Matches list with unread counts, and a chat that updates by itself every few seconds
- Edit your profile any time

## Important: OTP and password reset in demo mode

No SMS or email service is connected, so while `DEMO_MODE` is `true` in `config.php`, the OTP code and the password reset link are shown on the screen. Before you put the site online:

1. Set `DEMO_MODE` to `false`.
2. Add your SMS provider where `otp_login.php` says `TODO`, and your email sending where `forgot_password.php` says `TODO`.
3. Delete the demo accounts from the `users` table.

## Files

```
database.sql          the database (tables + demo data)
config.php            database login and settings
index.php             home page
register.php          sign up
login.php             log in (email + password)
otp_login.php         log in step 1 (mobile number)
verify_otp.php        log in step 2 (enter OTP)
forgot_password.php   ask for a reset link
reset_password.php    choose a new password
onboarding.php        choose job seeker or employer
profile.php           profile setup and editing
swipe.php             the swipe screen
matches.php           list of matches
chat.php              chat with a match
api/                  small helpers the swipe and chat screens talk to
includes/             shared page top, bottom and functions
assets/               styling and scripts
uploads/              pictures, logos and CVs people upload
```

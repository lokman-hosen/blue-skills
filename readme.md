1. Start the containers

./vendor/bin/sail up -d
This starts Postgres (on port 54320), Redis, Mailpit, Meilisearch and Reverb (on 8081). The app runs at http://localhost:8080.

2. Set up the database (first run, or to reset it)

./vendor/bin/sail artisan migrate:fresh --seed

3. Start the frontend, queue and scheduler

# Open three terminals and run one command in each:

1. Vite dev server (5173): ./vendor/bin/sail npm run dev
2. OTP codes, Voice-to-CV, SkillShow, notifications: ./vendor/bin/sail artisan queue:work
3. digests, quiet-hours alerts, mandate/evidence expiry (optional): ./vendor/bin/sail artisan schedule:work
4. If you don't need hot reload, run ./vendor/bin/sail npm run build once instead of npm run dev.

The queue worker is required, not optional. Without it, worker sign-in codes and video processing never run.

4. Sign in

- Worker: go to /join and enter +8801711000001 (Rahim Uddin). The code isn't sent anywhere; it's written to storage/logs/laravel.log:
  tail -f storage/logs/laravel.log | grep -i code
- Staff and organizations: use password password with accounts like superuser@blueskills.test, owner@abc-contracting.test, owner@northstar.test or principal@meghna.test. CLAUDE.md lists them all.
- Skipping staff 2FA: to turn off the staff 2FA requirement on your machine, set BLUESKILLS_ENFORCE_STAFF_2FA=false in .env. Tests still enforce it.
- Mailpit: emails go there. Its web UI is usually at http://localhost:8025.

Useful extras

./vendor/bin/sail php vendor/bin/pest --parallel   # tests
./vendor/bin/sail down                              # stop everything

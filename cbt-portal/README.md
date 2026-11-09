# MasterjeeCBT.Ai

AI-powered CBT mock-test portal for Indian competitive exams (SSC, UPSC, WB PSC, WB Police, banking, railways, JEE, NEET, CAT and more).

The application lives in [`php-app/`](php-app/) — a PHP + MySQL site with accounts, a TCS iON-style exam hall, English/বাংলা
questions, AI-generated and ready-made papers, and a scorecard with an AI coach.

**See [`php-app/README.md`](php-app/README.md) for the full feature list, setup and how it works.**

Quick start (macOS/Linux):

```bash
cd php-app
cp config.sample.php config.php
database/reset_db.sh
php -S localhost:8000 -t public     # http://localhost:8000
```

# RandWise

RandWise is a South African personal finance coach built with a Svelte frontend and a Laravel API.

It turns bank statement CSVs into clear ZAR-based dashboards, spending insights, recurring debit order detection, and realistic savings goals.

## Why this project exists

Most budgeting apps feel generic. RandWise is designed around South African money habits: debit orders, prepaid electricity, airtime and data, fuel, local banks, and rand-based goal planning.

This repo is built as a portfolio project for a junior full-stack role. It shows product thinking, frontend polish, backend API design, file uploads, data processing, and tests.

## Features

- Svelte dashboard for money health, cash flow, category spend, goals, and transactions
- Laravel API with `/api/demo` and `/api/statements/preview`
- CSV statement upload with transaction parsing
- Spending categorisation for South African expenses
- Recurring payment and debit order detection
- AI-style coach insights generated from real transaction data
- Privacy-focused controls and demo mode
- Feature tests for the financial insights API

The current coaching layer is deterministic so the demo works without paid API keys. It is structured so an LLM summary provider can be added later.

## Tech Stack

- Frontend: Svelte 5, TypeScript, Vite, Lucide icons
- Backend: Laravel 13, PHP 8.3
- Testing: PHPUnit, svelte-check, TypeScript
- Data format: CSV imports with sample Capitec-style data

## Project Structure

```text
randwise/
  api/          Laravel API
  web/          Svelte frontend
  sample-data/  Demo bank statement CSVs
  docs/         Portfolio and demo notes
```

## Local Setup

Run the Laravel API:

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Run the Svelte frontend in another terminal:

```bash
cd web
npm install
npm run dev
```

The frontend proxies `/api` requests to `http://127.0.0.1:8000`.

## Demo Data

Use this sample CSV in the upload flow:

```text
sample-data/capitec-demo-statement.csv
```

## Verification

```bash
cd api
composer test
```

```bash
cd web
npm run check
npm run build
```

## Portfolio Pitch

I built RandWise, a personal finance coach for South Africans. It imports bank statements, categorises local spending, detects recurring debit orders, and turns messy transaction history into practical monthly money actions.

# KohiCebu

KohiCebu is a coffee shop ordering and management system currently under development.

## Project Overview

The project aims to provide a digital platform for customers and coffee shop personnel to manage ordering and daily operations.

## System Components

- **Customer Website (`website/`)** — Customer-facing pages for browsing products, managing a cart, placing orders, and accessing customer accounts.
- **Backend API (`api/`)** — PHP endpoints for application operations, authentication, orders, products, inventory, and integrations.
- **Admin Portal (`admin/`)** — Administrative pages for managing products, inventory, staff, recipes, reports, and audit logs.
- **Staff Portal (`staff/`)** — Staff pages for managing orders, payments, and shift operations.
- **Android Application (`website/downloads/KohiCebu.apk`)** — An Android application package included in the current repository.

## Technologies Used

- PHP
- PostgreSQL
- HTML, CSS, and JavaScript
- Java and XML for Android development
- Composer for PHP dependency management

## Repository Structure

```text
KohiCebu/
├── admin/
├── api/
├── staff/
├── website/
├── composer.json
├── composer.lock
└── README.md
```

## Local Development Setup

1. Install the required PHP runtime and PostgreSQL.
2. Install Composer.
3. Clone or download the repository.
4. Configure the required environment variables using the provided environment template when available.
5. Create the PostgreSQL database and import the database schema when available.
6. Install PHP dependencies using `composer install`.
7. Configure the local web server and test the application components.

Some features may require additional configuration for Firebase, Google authentication, and online payments.

## Development Status

KohiCebu is still under development. Features, integrations, database documentation, and setup instructions may change as development continues. This repository represents the current development version and does not indicate that the system is production-ready.

## Repository URL

https://github.com/loyolajohnmichael1-debug/KohiCebu

## Security

- Never commit the actual `.env` file.
- Do not publish passwords, API keys, payment secrets, or Firebase service-account credentials.
- Use placeholder values in environment configuration examples.
- Use fictional sample data for demonstrations and testing.
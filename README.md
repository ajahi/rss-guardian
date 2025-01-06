
# Guardian API RSS Feed Application

This is a PHP application built with the Slim framework to fetch data from the Guardian API and display it in RSS format.

## Features

- Fetches section-specific data from the Guardian API.
- Converts the API response into an RSS feed.
- Default section is `world` if no section is specified in the URL.

## Requirements

- PHP 7.4 or higher
- Composer
- Guardian API key

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/your-repo/guardian-api-rss.git
   cd guardian-api-rss
   ```

2. Install dependencies using Composer:
   ```bash
   composer install
   ```

3. Configure the Guardian API key:
   - Update the API key in `App/Controllers/GuardianController.php` with your Guardian API key.

4. Start the server:
   ```bash
   php -S localhost:8000 index.php
   ```

## Usage

- Visit `http://localhost:8000/` to view the RSS feed for the default `world` section.
- Specify a section in the URL to view its RSS feed, e.g.:
  - `http://localhost:8000/sports`
  - `http://localhost:8000/tech`

## Folder Structure

```
.
├── App/
│   └── Controllers/
│       └── GuardianController.php
├── vendor/
├── composer.json
├── index.php
```

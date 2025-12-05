# Quickstart: Lunar Quanta Framework

**Date**: 2025-12-03
**Branch**: `001-framework-baseline`

## Prerequisites

- PHP 8.3+
- Composer
- Extensions: mbstring, json, openssl

## Installation

```bash
# Clone the repository
git clone https://github.com/your-username/lunar-quanta.git
cd lunar-quanta

# Install dependencies
composer install

# Create cache directories
mkdir -p cache/template public/cache/template

# Set permissions (if needed)
chmod 755 cache/ log/ -R
```

## Quick Start

### 1. Start the Development Server

```bash
bin/console server:start
```

Visit `http://localhost:8000` to see the welcome page.

### 2. Create Your First Controller

```bash
bin/console make:controller HelloController
```

This generates `src/Controller/HelloController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Attribute\Route;
use App\Service\Core\BaseController;
use App\Service\Core\Http\Request;
use App\Service\Core\Http\Response;

class HelloController extends BaseController
{
    #[Route('/hello', name: 'hello_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return new Response($this->render('hello/index', [
            'title' => 'Hello World',
            'message' => 'Welcome to Lunar Quanta!'
        ]));
    }

    #[Route('/hello/{name}', name: 'hello_greet', methods: ['GET'])]
    public function greet(Request $request): Response
    {
        $name = $request->getParameter('name');
        return new Response($this->render('hello/greet', [
            'name' => $name
        ]));
    }
}
```

### 3. Create a Template

Create `template/hello/index.html.tpl`:

```html
[% extends 'base.html.tpl' %]

[% block title %]Hello World[% endblock %]

[% block content %]
<h1>[[ title ]]</h1>
<p>[[ message ]]</p>
<a href="##url('hello_greet', {'name': 'World'})##">Say Hello</a>
[% endblock %]
```

### 4. Clear the Cache

```bash
bin/console cache:clear
```

### 5. Visit Your Route

Navigate to `http://localhost:8000/hello`

## Template Syntax

### Variables

```html
[[ variable ]]              <!-- Auto-escaped output -->
[[ user.name ]]             <!-- Object/array access -->
[[! rawHtml !]]             <!-- Unescaped (use with caution) -->
```

### Control Structures

```html
[% if condition %]
  Content when true
[% else %]
  Content when false
[% endif %]

[% for item in items %]
  [[ item.name ]]
[% endfor %]
```

### Template Inheritance

```html
<!-- base.html.tpl -->
<!DOCTYPE html>
<html>
<head><title>[% block title %]Default[% endblock %]</title></head>
<body>
  [% block content %][% endblock %]
</body>
</html>

<!-- child.html.tpl -->
[% extends 'base.html.tpl' %]

[% block title %]My Page[% endblock %]

[% block content %]
  <h1>Hello!</h1>
[% endblock %]
```

## CLI Commands

| Command | Description |
|---------|-------------|
| `bin/console` | List all commands |
| `bin/console server:start` | Start dev server |
| `bin/console server:stop` | Stop dev server |
| `bin/console cache:clear` | Clear all caches |
| `bin/console router:debug` | Show all routes |
| `bin/console make:controller Name` | Generate controller |
| `bin/console make:command name:action` | Generate command |
| `bin/console filesystem:tree` | Show project structure |

## Environment Configuration

Create `.env` file:

```env
APP_ENV=dev
APP_DEBUG=true
APP_SECRET=your-secret-key-here
```

## Project Structure

```
lunar-quanta/
├── bin/console          # CLI entry point
├── public/index.php     # Web entry point
├── src/
│   ├── Controller/      # Your controllers
│   ├── Command/         # Your commands
│   └── Service/         # Framework services
├── template/            # Your templates
├── config/              # Configuration files
└── cache/               # Compiled routes/templates
```

## Next Steps

1. Read the [README](../../README.md) for full documentation
2. Explore `bin/console router:debug` to see available routes
3. Create more controllers and templates
4. Check `doc/command.md` for CLI development guide

## Verification Checklist

- [ ] Server starts without errors
- [ ] Welcome page loads at `http://localhost:8000`
- [ ] Custom route `/hello` works
- [ ] Template renders with variables
- [ ] CLI commands execute successfully

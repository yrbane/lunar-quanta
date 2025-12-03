# Lunar Template Engine

[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Lunar Template Engine** is a standalone, advanced template engine for PHP 8.3+ featuring template inheritance, blocks, macros, and intelligent caching.

## Features

✨ **Template Inheritance** - Extend templates with `[% extends 'parent.tpl' %]`  
🧱 **Block System** - Override parent blocks with `[% block content %]`  
⚡ **Macros** - Reusable components with `##macroName(args)##`  
🚀 **Smart Caching** - Automatic compilation and caching  
🔒 **Secure** - XSS protection with automatic HTML escaping  
📝 **Clean Syntax** - Intuitive template syntax  
🎯 **Standalone** - No dependencies, framework agnostic  

## Installation

```bash
composer require lunar/template
```

## Quick Start

```php
<?php
use Lunar\Template\AdvancedTemplateEngine;

// Initialize the engine
$engine = new AdvancedTemplateEngine(
    templatePath: '/path/to/templates',
    cachePath: '/path/to/cache'
);

// Render a template
$html = $engine->render('blog/article', [
    'title' => 'My Article',
    'content' => 'Article content...',
    'author' => 'John Doe'
]);

echo $html;
```

## Template Syntax

### Variables

```html
<!-- Simple variable -->
<h1>[[ title ]]</h1>

<!-- Object property -->
<p>By [[ author.name ]]</p>

<!-- Array access -->
<span>[[ tags.0 ]]</span>
```

### Conditions

```html
[% if user.isLoggedIn %]
    <p>Welcome, [[ user.name ]]!</p>
[% elseif user.isGuest %]
    <p>Hello, guest!</p>
[% else %]
    <p>Please log in.</p>
[% endif %]
```

### Loops

```html
[% for article in articles %]
    <article>
        <h2>[[ article.title ]]</h2>
        <p>[[ article.excerpt ]]</p>
    </article>
[% endfor %]
```

### Template Inheritance

**Base template (`base.html.tpl`):**
```html
<!DOCTYPE html>
<html>
<head>
    <title>[% block title %]Default Title[% endblock %]</title>
</head>
<body>
    <header>
        <h1>My Website</h1>
    </header>
    
    <main>
        [% block content %]
            Default content
        [% endblock %]
    </main>
    
    <footer>
        [% block footer %]
            <p>&copy; 2024 My Website</p>
        [% endblock %]
    </footer>
</body>
</html>
```

**Child template (`article.html.tpl`):**
```html
[% extends 'base.html.tpl' %]

[% block title %][[ article.title ]] - My Blog[% endblock %]

[% block content %]
    <article>
        <h1>[[ article.title ]]</h1>
        <time>[[ article.publishedAt ]]</time>
        <div>[[ article.content ]]</div>
    </article>
[% endblock %]
```

### Macros

**Register a macro:**
```php
$engine->registerMacro('url', function($routeName, $params = []) {
    return "/route/{$routeName}?" . http_build_query($params);
});
```

**Use in template:**
```html
<a href="##url('blog.show', ['id' => article.id])##">Read more</a>
```

**Built-in macros:**
```html
<!-- Asset URLs -->
<link rel="stylesheet" href="##asset('/css/app.css')##">
<script src="##asset('/js/app.js')##"></script>
```

## Advanced Usage

### Custom Macro Classes

```php
<?php
use Lunar\Template\Macro\MacroInterface;

class UrlMacro implements MacroInterface
{
    private RouterInterface $router;
    
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }
    
    public function getName(): string
    {
        return 'url';
    }
    
    public function execute(array $args): string
    {
        $routeName = $args[0] ?? '';
        return $this->router->generateUrl($routeName);
    }
}

// Register the macro
$engine->registerMacroInstance(new UrlMacro($router));
```

### Load Macros from Directory

```php
$engine->loadMacrosFromDirectory(
    'App\\Template\\Macro',
    '/path/to/macros'
);
```

### Template Management

```php
// Check if template exists
if ($engine->templateExists('blog/article')) {
    $html = $engine->render('blog/article', $data);
}

// Clear cache
$engine->clearCache(); // Clear all
$engine->clearCache('blog/article'); // Clear specific template

// Get registered macros
$macros = $engine->getRegisteredMacros();
```

### Error Handling

```php
use Lunar\Template\Exception\TemplateException;

try {
    $html = $engine->render('non-existent-template');
} catch (TemplateException $e) {
    echo "Template error: " . $e->getMessage();
}
```

## Security

- **XSS Protection**: All variables are automatically HTML-escaped
- **Path Validation**: Template paths are validated and normalized
- **Safe Compilation**: Compiled templates are stored securely

## Performance

- **Smart Caching**: Templates are recompiled only when source changes
- **Optimized Compilation**: Efficient regex-based parsing
- **Memory Efficient**: Minimal memory footprint
- **OPcache Compatible**: Works great with PHP OPcache

## Configuration

### Custom Default Variables

```php
class CustomTemplateEngine extends AdvancedTemplateEngine
{
    protected function setDefaultVariables(): void
    {
        parent::setDefaultVariables();
        
        // Add your custom defaults
        if (!isset($siteName)) $siteName = 'My Website';
        if (!isset($currentYear)) $currentYear = date('Y');
    }
}
```

## Requirements

- **PHP 8.3+**
- **ext-mbstring** (for string handling)

## Framework Integration

### Laravel

```php
// In a Service Provider
$this->app->singleton(AdvancedTemplateEngine::class, function ($app) {
    return new AdvancedTemplateEngine(
        resource_path('templates'),
        storage_path('framework/cache/templates')
    );
});
```

### Symfony

```yaml
# services.yaml
services:
    Lunar\Template\AdvancedTemplateEngine:
        arguments:
            $templatePath: '%kernel.project_dir%/templates'
            $cachePath: '%kernel.cache_dir%/templates'
```

## Contributing

Contributions are welcome! Please read our [Contributing Guidelines](CONTRIBUTING.md) for details.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Changelog

### v1.0.0
- Initial release
- Template inheritance system
- Block system
- Macro system
- Smart caching
- XSS protection
- Framework agnostic design

---

**Happy templating! 🎉**
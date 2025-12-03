<?php
declare(strict_types=1);

namespace Lunar\Template\Tests\Integration;

use Lunar\Template\AdvancedTemplateEngine;
use Lunar\Template\Macro\AssetMacro;
use Lunar\Template\Macro\UrlMacro;
use Lunar\Template\Macro\RouterInterface;
use PHPUnit\Framework\TestCase;

class FullTemplateSystemTest extends TestCase
{
    private string $templatesDir;
    private string $cacheDir;
    private AdvancedTemplateEngine $engine;

    protected function setUp(): void
    {
        $this->templatesDir = sys_get_temp_dir() . '/lunar-integration-templates-' . uniqid();
        $this->cacheDir = sys_get_temp_dir() . '/lunar-integration-cache-' . uniqid();
        
        mkdir($this->templatesDir, 0755, true);
        
        $this->engine = new AdvancedTemplateEngine($this->templatesDir, $this->cacheDir);
        $this->setupMacros();
        $this->createTemplates();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templatesDir);
        if (is_dir($this->cacheDir)) {
            $this->removeDirectory($this->cacheDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function setupMacros(): void
    {
        // Asset macro
        $this->engine->registerMacroInstance(new AssetMacro('/assets'));
        
        // URL macro with mock router
        $router = new class implements RouterInterface {
            private array $routes = [
                'home' => ['path' => '/'],
                'blog.index' => ['path' => '/blog'],
                'blog.show' => ['path' => '/blog/post'],
                'user.profile' => ['path' => '/user/profile']
            ];
            
            public function getRouteByName(string $name): ?array
            {
                return $this->routes[$name] ?? null;
            }
        };
        
        $this->engine->registerMacroInstance(new UrlMacro($router));
        
        // Custom macro
        $this->engine->registerMacro('format_date', function($timestamp, $format = 'Y-m-d') {
            return date($format, is_string($timestamp) ? strtotime($timestamp) : $timestamp);
        });
    }

    private function createTemplates(): void
    {
        // Base layout
        $baseTemplate = <<<'TPL'
<!DOCTYPE html>
<html lang="[[ lang ]]">
<head>
    <meta charset="[[ charset ]]">
    <meta name="viewport" content="[[ viewport ]]">
    <title>[% block title %]Default Title[% endblock %] - My Website</title>
    <link rel="stylesheet" href="##asset('css/main.css')##">
    [% block head %][% endblock %]
</head>
<body>
    <nav class="navbar">
        <a href="##url('home')##">Home</a>
        <a href="##url('blog.index')##">Blog</a>
        [% if user %]
            <a href="##url('user.profile')##">Profile</a>
        [% else %]
            <a href="/login">Login</a>
        [% endif %]
    </nav>
    
    <main>
        [% block content %]
            <p>Default content</p>
        [% endblock %]
    </main>
    
    <footer>
        <p>&copy; 2024 My Website. All rights reserved.</p>
        [% block footer %][% endblock %]
    </footer>
    
    <script src="##asset('js/main.js')##"></script>
    [% block scripts %][% endblock %]
</body>
</html>
TPL;
        file_put_contents($this->templatesDir . '/base.tpl', $baseTemplate);

        // Blog listing template
        $blogTemplate = <<<'TPL'
[% extends 'base.tpl' %]

[% block title %]Blog Posts[% endblock %]

[% block head %]
<meta name="description" content="Latest blog posts">
<link rel="stylesheet" href="##asset('css/blog.css')##">
[% endblock %]

[% block content %]
<h1>Latest Blog Posts</h1>

[% if posts %]
    <div class="posts-grid">
    [% for post in posts %]
        <article class="post-card">
            [% if post.featured_image %]
                <img src="##asset(post.featured_image)##" alt="[[ post.title ]]" class="post-image">
            [% endif %]
            
            <div class="post-content">
                <h2>
                    <a href="##url('blog.show')##?id=[[ post.id ]]">[[ post.title ]]</a>
                </h2>
                
                <div class="post-meta">
                    <span class="author">By [[ post.author.name ]]</span>
                    <span class="date">##format_date(post.published_at, 'F j, Y')##</span>
                    [% if post.category %]
                        <span class="category">[[ post.category ]]</span>
                    [% endif %]
                </div>
                
                <p class="excerpt">[[ post.excerpt ]]</p>
                
                [% if post.tags %]
                    <div class="tags">
                    [% for tag in post.tags %]
                        <span class="tag">[[ tag ]]</span>
                    [% endfor %]
                    </div>
                [% endif %]
                
                <a href="##url('blog.show')##?id=[[ post.id ]]" class="read-more">Read More</a>
            </div>
        </article>
    [% endfor %]
    </div>
    
    [% if pagination.has_more %]
        <div class="pagination">
            [% if pagination.prev_page %]
                <a href="?page=[[ pagination.prev_page ]]" class="prev">Previous</a>
            [% endif %]
            
            <span class="current">Page [[ pagination.current_page ]] of [[ pagination.total_pages ]]</span>
            
            [% if pagination.next_page %]
                <a href="?page=[[ pagination.next_page ]]" class="next">Next</a>
            [% endif %]
        </div>
    [% endif %]
[% else %]
    <div class="no-posts">
        <h2>No posts yet</h2>
        <p>Check back later for new content!</p>
    </div>
[% endif %]
[% endblock %]

[% block scripts %]
<script src="##asset('js/blog.js')##"></script>
[% endblock %]
TPL;
        file_put_contents($this->templatesDir . '/blog.tpl', $blogTemplate);

        // User dashboard template
        $dashboardTemplate = <<<'TPL'
[% extends 'base.tpl' %]

[% block title %]Dashboard - [[ user.name ]][% endblock %]

[% block content %]
<div class="dashboard">
    <header class="dashboard-header">
        <h1>Welcome back, [[ user.name ]]!</h1>
        [% if user.avatar %]
            <img src="##asset(user.avatar)##" alt="Avatar" class="avatar">
        [% endif %]
    </header>
    
    <div class="dashboard-stats">
        [% for stat in stats %]
            <div class="stat-card [[ stat.type ]]">
                <h3>[[ stat.label ]]</h3>
                <div class="value">[[ stat.value ]]</div>
                [% if stat.change %]
                    <div class="change [% if stat.change > 0 %]positive[% else %]negative[% endif %]">
                        [[ stat.change ]]%
                    </div>
                [% endif %]
            </div>
        [% endfor %]
    </div>
    
    [% if recent_activities %]
        <section class="recent-activities">
            <h2>Recent Activities</h2>
            <ul class="activity-list">
            [% for activity in recent_activities %]
                <li class="activity-item [[ activity.type ]]">
                    <div class="activity-content">
                        <strong>[[ activity.title ]]</strong>
                        <p>[[ activity.description ]]</p>
                    </div>
                    <time class="activity-time">##format_date(activity.created_at, 'M j, g:i A')##</time>
                </li>
            [% endfor %]
            </ul>
        </section>
    [% endif %]
</div>
[% endblock %]
TPL;
        file_put_contents($this->templatesDir . '/dashboard.tpl', $dashboardTemplate);
    }

    public function testFullBlogTemplateRendering(): void
    {
        $data = [
            'lang' => 'en',
            'charset' => 'UTF-8',
            'viewport' => 'width=device-width, initial-scale=1.0',
            'user' => ['name' => 'John Doe'],
            'posts' => [
                [
                    'id' => 1,
                    'title' => 'Getting Started with PHP 8.3',
                    'excerpt' => 'Learn about the latest features in PHP 8.3 including improved performance and new syntax.',
                    'author' => ['name' => 'Jane Smith'],
                    'published_at' => '2024-03-15',
                    'category' => 'PHP',
                    'tags' => ['PHP', 'Tutorial', 'Beginner'],
                    'featured_image' => 'images/php83.jpg'
                ],
                [
                    'id' => 2,
                    'title' => 'Advanced Template Patterns',
                    'excerpt' => 'Explore advanced patterns for template inheritance and macro usage.',
                    'author' => ['name' => 'Bob Wilson'],
                    'published_at' => '2024-03-10',
                    'category' => 'Templates',
                    'tags' => ['Templates', 'Advanced'],
                    'featured_image' => null
                ]
            ],
            'pagination' => [
                'current_page' => 1,
                'total_pages' => 3,
                'has_more' => true,
                'next_page' => 2,
                'prev_page' => null
            ]
        ];

        $result = $this->engine->render('blog', $data);

        // Check basic structure
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html lang="en">', $result);
        $this->assertStringContainsString('<title>Blog Posts - My Website</title>', $result);

        // Check navigation
        $this->assertStringContainsString('<a href="/">Home</a>', $result);
        $this->assertStringContainsString('<a href="/blog">Blog</a>', $result);
        $this->assertStringContainsString('<a href="/user/profile">Profile</a>', $result);

        // Check assets
        $this->assertStringContainsString('href="/assets/css/main.css"', $result);
        $this->assertStringContainsString('href="/assets/css/blog.css"', $result);
        $this->assertStringContainsString('src="/assets/js/main.js"', $result);
        $this->assertStringContainsString('src="/assets/js/blog.js"', $result);

        // Check post content
        $this->assertStringContainsString('Getting Started with PHP 8.3', $result);
        $this->assertStringContainsString('Advanced Template Patterns', $result);
        $this->assertStringContainsString('By Jane Smith', $result);
        $this->assertStringContainsString('By Bob Wilson', $result);

        // Check formatted dates
        $this->assertStringContainsString('March 15, 2024', $result);
        $this->assertStringContainsString('March 10, 2024', $result);

        // Check tags
        $this->assertStringContainsString('PHP', $result);
        $this->assertStringContainsString('Tutorial', $result);
        $this->assertStringContainsString('Advanced', $result);

        // Check featured image
        $this->assertStringContainsString('src="/assets/images/php83.jpg"', $result);

        // Check pagination
        $this->assertStringContainsString('Page 1 of 3', $result);
        $this->assertStringContainsString('href="?page=2"', $result);

        // Check URL generation
        $this->assertStringContainsString('href="/blog/post?id=1"', $result);
        $this->assertStringContainsString('href="/blog/post?id=2"', $result);
    }

    public function testDashboardTemplateRendering(): void
    {
        $data = [
            'user' => [
                'name' => 'Alice Johnson',
                'avatar' => 'images/avatars/alice.jpg'
            ],
            'stats' => [
                [
                    'type' => 'posts',
                    'label' => 'Total Posts',
                    'value' => 42,
                    'change' => 12
                ],
                [
                    'type' => 'views',
                    'label' => 'Page Views',
                    'value' => '1.2K',
                    'change' => -5
                ],
                [
                    'type' => 'comments',
                    'label' => 'Comments',
                    'value' => 89,
                    'change' => 0
                ]
            ],
            'recent_activities' => [
                [
                    'type' => 'post',
                    'title' => 'New Post Published',
                    'description' => 'Published "Advanced PHP Patterns"',
                    'created_at' => time() - 3600 // 1 hour ago
                ],
                [
                    'type' => 'comment',
                    'title' => 'New Comment',
                    'description' => 'Someone commented on your post',
                    'created_at' => time() - 7200 // 2 hours ago
                ]
            ]
        ];

        $result = $this->engine->render('dashboard', $data);

        // Check basic structure
        $this->assertStringContainsString('<title>Dashboard - Alice Johnson - My Website</title>', $result);
        $this->assertStringContainsString('Welcome back, Alice Johnson!', $result);

        // Check avatar
        $this->assertStringContainsString('src="/assets/images/avatars/alice.jpg"', $result);

        // Check stats
        $this->assertStringContainsString('Total Posts', $result);
        $this->assertStringContainsString('42', $result);
        $this->assertStringContainsString('Page Views', $result);
        $this->assertStringContainsString('1.2K', $result);

        // Check conditional classes for changes
        $this->assertStringContainsString('positive', $result); // 12% change
        $this->assertStringContainsString('negative', $result); // -5% change

        // Check activities
        $this->assertStringContainsString('New Post Published', $result);
        $this->assertStringContainsString('Advanced PHP Patterns', $result);
        $this->assertStringContainsString('New Comment', $result);

        // Check formatted times (should contain today's date)
        $today = date('M j');
        $this->assertStringContainsString($today, $result);
    }

    public function testTemplateWithoutData(): void
    {
        $result = $this->engine->render('blog', []);

        // Should render with defaults and empty data
        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('No posts yet', $result);
        $this->assertStringContainsString('<a href="/login">Login</a>', $result); // No user
    }

    public function testCachePerformance(): void
    {
        $data = ['posts' => []];

        // First render - should compile
        $start = microtime(true);
        $result1 = $this->engine->render('blog', $data);
        $firstRenderTime = microtime(true) - $start;

        // Second render - should use cache
        $start = microtime(true);
        $result2 = $this->engine->render('blog', $data);
        $secondRenderTime = microtime(true) - $start;

        // Results should be identical
        $this->assertSame($result1, $result2);

        // Second render should be faster (cached)
        $this->assertLessThan($firstRenderTime, $secondRenderTime);

        // Verify cache files exist
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertGreaterThan(0, count($cacheFiles));
    }

    public function testComplexDataStructures(): void
    {
        $complexData = [
            'user' => [
                'name' => 'Complex User',
                'preferences' => [
                    'theme' => 'dark',
                    'notifications' => [
                        'email' => true,
                        'push' => false
                    ]
                ]
            ],
            'nested' => [
                'level1' => [
                    'level2' => [
                        'level3' => 'Deep Value'
                    ]
                ]
            ]
        ];

        // Create a template that uses complex data
        $complexTemplate = <<<'TPL'
User: [[ user.name ]]
Theme: [[ user.preferences.theme ]]
Email: [% if user.preferences.notifications.email %]Enabled[% else %]Disabled[% endif %]
Deep: [[ nested.level1.level2.level3 ]]
TPL;
        file_put_contents($this->templatesDir . '/complex.tpl', $complexTemplate);

        $result = $this->engine->render('complex', $complexData);

        $this->assertStringContainsString('User: Complex User', $result);
        $this->assertStringContainsString('Theme: dark', $result);
        $this->assertStringContainsString('Email: Enabled', $result);
        $this->assertStringContainsString('Deep: Deep Value', $result);
    }
}
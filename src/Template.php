<?php

declare(strict_types=1);

namespace PhpSsg;

/**
 * Native PHP template engine.
 *
 * Templates are plain PHP files. Variables passed to render() are extracted
 * into scope. The template can call $this->layout() to wrap output in a
 * parent layout, $this->e() for HTML escaping, and $this->asset() for
 * fingerprinted asset URLs (populated by the asset pipeline).
 */
class Template
{
    private string $templateDir;
    private array $globals = [];
    private ?string $layoutFile = null;
    private array $layoutVars = [];
    private array $assetMap = [];
    private array $sections = [];
    private array $sectionStack = [];

    public function __construct(string $templateDir, array $globals = [])
    {
        $this->templateDir = rtrim($templateDir, '/');
        $this->globals = $globals;
    }

    public function setAssetMap(array $map): void
    {
        $this->assetMap = $map;
    }

    public function render(string $template, array $vars = []): string
    {
        $this->layoutFile = null;
        $this->layoutVars = [];

        $content = $this->renderFile($template, $vars);

        if ($this->layoutFile !== null) {
            $layout = $this->layoutFile;
            $layoutVars = array_merge($this->layoutVars, ['content' => $content]);
            $this->layoutFile = null;
            $this->layoutVars = [];
            return $this->renderFile($layout, $layoutVars);
        }

        return $content;
    }

    public function layout(string $name, array $vars = []): void
    {
        $this->layoutFile = $name;
        $this->layoutVars = $vars;
    }

    public function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function asset(string $path): string
    {
        return $this->assetMap[$path] ?? $path;
    }

    public function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->sectionStack === []) {
            throw new \RuntimeException('endSection() called without matching section()');
        }
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    private function renderFile(string $name, array $vars): string
    {
        $file = $this->templateDir . '/' . ltrim($name, '/');
        if (!str_ends_with($file, '.php')) {
            $file .= '.php';
        }
        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: {$file}");
        }

        $vars = array_merge($this->globals, $vars);

        ob_start();
        (function (string $__file__, array $__vars__) {
            extract($__vars__, EXTR_SKIP);
            require $__file__;
        })->call($this, $file, $vars);
        return ob_get_clean();
    }
}

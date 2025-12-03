<?php
/**
 * Exception spécifique au moteur de templates.
 *
 * @since 1.0.0
 * @author seb@nethttp.net
 */
declare(strict_types=1);

namespace Lunar\Template\Exception;

class TemplateException extends \Exception
{
    public static function templateNotFound(string $template): self
    {
        return new self("Template not found: {$template}");
    }

    public static function unableToReadTemplate(string $template): self
    {
        return new self("Unable to read template: {$template}");
    }

    public static function unableToCreateCacheDirectory(string $path): self
    {
        return new self("Unable to create cache directory: {$path}");
    }

    public static function directoryNotReadable(string $path): self
    {
        return new self("Directory is not readable: {$path}");
    }

    public static function directoryNotFound(string $path): self
    {
        return new self("Directory does not exist: {$path}");
    }

    public static function directoryNotWritable(string $path): self
    {
        return new self("Directory is not writable: {$path}");
    }

    public static function macroNotFound(string $name): self
    {
        return new self("Macro '{$name}' is not defined");
    }

    public static function parentTemplateNotFound(string $parent): self
    {
        return new self("Parent template not found: {$parent}");
    }
}
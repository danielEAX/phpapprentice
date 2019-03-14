<?php

namespace Apprentice;

use Parsedown;

/**
 * Handles building pages in public folder
 * using configuration based on Page classes
 */
class Build
{
    /**
     * Builds all pages in config
     *
     * @return void
     */
    public function buildAll(): void
    {
        $this->createOutputFolder();
        $this->cleanOutputFolder();
        $this->copyFiles();

        $pages = config('pages');
        foreach ($pages as $page) {
            $this->buildPage($page);
        }
    }

    /**
     * Builds single page in config
     *
     * @param  string $name Name of page to build
     * @return string       Contents of built page
     */
    public function runSingleBuild(string $name): string
    {
        $this->createOutputFolder();

        $pages = config('pages');

        foreach ($pages as $page) {
            if ($page->name == $name) {
                break;
            }
        }

        return $this->buildPage($page);
    }

    /**
     * Deletes all html files in public folder
     *
     * @return void
     */
    private function cleanOutputFolder(): void
    {
        $files = glob(config('output_dir') . '/*.html');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function copyFiles(): void
    {
        $filesDir = config('files_dir');
        if (!file_exists($filesDir)) {
            return;
        }

        $outputDir = config('output_dir');
        foreach (glob($filesDir . '/*') as $file) {
            $name = basename($file);

            copy($file, $outputDir . '/' . $name);
        }
    }

    /**
     * Creates output folder if it does not exist
     *
     * @return void
     */
    private function createOutputFolder(): void
    {
        if (!file_exists(config('output_dir'))) {
            mkdir(config('output_dir'));
        }
    }

    /**
     * Builds single Page into html and
     * outputs to public directory
     *
     * @param  Page   $page Page from config to be built
     * @return void         Contents of built page
     */
    private function buildPage(Page $page): string
    {
        if (!empty($page->chapter)) {
            if (!file_exists(config('chapter_dir') . '/' . $page->chapter)) {
                throw new \Exception('Code file not found: ' . $page->chapter);
            }

            $parser = new Parsedown();

            $content = file_get_contents(config('chapter_dir') . '/' . $page->chapter);
            $page->variables['chapter'] = $parser->text($content);
        }

        if ($page->template) {
            $template = config('templates_dir') . '/' . $page->template;
        } else {
            $template = config('templates_dir') . '/default.phtml';
        }
        $output = $this->getOutput($template, $page->variables);

        $path = config('output_dir') . '/' . $page->name . '.html';
        $dir = pathinfo($path, PATHINFO_DIRNAME);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, $output);

        return $output;
    }

    /**
     * Loads template file with scoped
     * variables
     *
     * @param  string $path Path to template in templates folder
     * @param  array  $vars Array of variables to be loaded in template
     * @return string       Output of template
     */
    private function getOutput(string $path, array $vars = []): string {
        if ($vars) {
            extract($vars);
        }

        ob_start();
        require $path;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}

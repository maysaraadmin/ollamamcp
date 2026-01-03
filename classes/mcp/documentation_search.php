<?php
namespace local_ollamamcp\mcp;

defined('MOODLE_INTERNAL') || die();

/**
 * Documentation search and retrieval system for MCP
 */
class documentation_search {
    
    private $devdocs_path;
    private $phpdocs_path;
    
    public function __construct() {
        global $CFG;
        $plugin_path = $CFG->dirroot . '/local/ollamamcp';
        $this->devdocs_path = $plugin_path . '/devdocs';
        $this->phpdocs_path = $plugin_path . '/phpdocs';
    }
    
    /**
     * Search documentation for relevant content
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    public function search_documentation($query, $options = []) {
        $results = [
            'devdocs' => $this->search_devdocs($query, $options),
            'phpdocs' => $this->search_phpdocs($query, $options)
        ];
        
        return $results;
    }
    
    /**
     * Search Moodle developer documentation
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    private function search_devdocs($query, $options = []) {
        $results = [];
        $limit = $options['limit'] ?? 5;
        
        // Search in README files first
        $readme_files = $this->find_files($this->devdocs_path, 'README.md');
        foreach ($readme_files as $file) {
            $content = file_get_contents($file);
            if ($this->content_matches_query($content, $query)) {
                $results[] = [
                    'type' => 'devdocs',
                    'file' => str_replace($this->devdocs_path . '/', '', $file),
                    'title' => $this->extract_title($content),
                    'content' => $this->extract_relevant_content($content, $query),
                    'relevance' => $this->calculate_relevance($content, $query)
                ];
            }
        }
        
        // Search in docs directory
        $docs_path = $this->devdocs_path . '/docs';
        if (is_dir($docs_path)) {
            $md_files = $this->find_files($docs_path, '*.md');
            foreach ($md_files as $file) {
                $content = file_get_contents($file);
                if ($this->content_matches_query($content, $query)) {
                    $results[] = [
                        'type' => 'devdocs',
                        'file' => str_replace($this->devdocs_path . '/', '', $file),
                        'title' => $this->extract_title($content),
                        'content' => $this->extract_relevant_content($content, $query),
                        'relevance' => $this->calculate_relevance($content, $query)
                    ];
                }
            }
        }
        
        // Sort by relevance and limit results
        usort($results, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Search Moodle PHP documentation
     * @param string $query Search query
     * @param array $options Search options
     * @return array Search results
     */
    private function search_phpdocs($query, $options = []) {
        $results = [];
        $limit = $options['limit'] ?? 5;
        
        // Search in generated PHP documentation
        $phpdocs_generated = $this->phpdocs_path . '/phpdocs';
        if (is_dir($phpdocs_generated)) {
            $html_files = $this->find_files($phpdocs_generated, '*.html');
            foreach ($html_files as $file) {
                $content = file_get_contents($file);
                if ($this->content_matches_query($content, $query)) {
                    $results[] = [
                        'type' => 'phpdocs',
                        'file' => str_replace($this->phpdocs_path . '/', '', $file),
                        'title' => $this->extract_html_title($content),
                        'content' => $this->extract_html_content($content, $query),
                        'relevance' => $this->calculate_relevance($content, $query)
                    ];
                }
            }
        }
        
        // Sort by relevance and limit results
        usort($results, function($a, $b) {
            return $b['relevance'] - $a['relevance'];
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Find files matching pattern
     * @param string $directory Directory to search
     * @param string $pattern File pattern
     * @return array Found files
     */
    private function find_files($directory, $pattern) {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && fnmatch($pattern, $file->getFilename())) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Check if content matches query
     * @param string $content Content to check
     * @param string $query Search query
     * @return bool Match found
     */
    private function content_matches_query($content, $query) {
        $keywords = explode(' ', strtolower($query));
        $content_lower = strtolower($content);
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2 && strpos($content_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract title from content
     * @param string $content Content
     * @return string Title
     */
    private function extract_title($content) {
        // Try to extract from markdown title
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        
        // Try to extract from HTML title
        if (preg_match('/<title>(.+)<\/title>/i', $content, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        
        return 'Untitled';
    }
    
    /**
     * Extract HTML title
     * @param string $content HTML content
     * @return string Title
     */
    private function extract_html_title($content) {
        if (preg_match('/<h1[^>]*>(.+)<\/h1>/i', $content, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        
        return $this->extract_title($content);
    }
    
    /**
     * Extract relevant content around query
     * @param string $content Full content
     * @param string $query Search query
     * @return string Relevant excerpt
     */
    private function extract_relevant_content($content, $query) {
        $keywords = explode(' ', $query);
        $lines = explode("\n", $content);
        $relevant_lines = [];
        
        foreach ($lines as $index => $line) {
            $line_lower = strtolower($line);
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 2 && strpos($line_lower, $keyword) !== false) {
                    // Include surrounding lines for context
                    $start = max(0, $index - 2);
                    $end = min(count($lines) - 1, $index + 2);
                    
                    for ($i = $start; $i <= $end; $i++) {
                        if (!isset($relevant_lines[$i])) {
                            $relevant_lines[$i] = $lines[$i];
                        }
                    }
                    break;
                }
            }
        }
        
        ksort($relevant_lines);
        $excerpt = implode("\n", $relevant_lines);
        
        // Clean up markdown
        $excerpt = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $excerpt); // Remove links
        $excerpt = preg_replace('/[#*`_]/', '', $excerpt); // Remove markdown chars
        
        return substr(trim($excerpt), 0, 500) . (strlen($excerpt) > 500 ? '...' : '');
    }
    
    /**
     * Extract HTML content
     * @param string $content HTML content
     * @param string $query Search query
     * @return string Relevant excerpt
     */
    private function extract_html_content($content, $query) {
        // Remove HTML tags but keep text
        $text = strip_tags($content);
        return $this->extract_relevant_content($text, $query);
    }
    
    /**
     * Calculate relevance score
     * @param string $content Content
     * @param string $query Search query
     * @return float Relevance score
     */
    private function calculate_relevance($content, $query) {
        $keywords = explode(' ', strtolower($query));
        $content_lower = strtolower($content);
        $score = 0;
        
        foreach ($keywords as $keyword) {
            if (strlen($keyword) > 2) {
                $occurrences = substr_count($content_lower, $keyword);
                $score += $occurrences * strlen($keyword);
            }
        }
        
        return $score;
    }
    
    /**
     * Format search results for AI context
     * @param array $results Search results
     * @return string Formatted context
     */
    public function format_results_for_ai($results) {
        $context = "Relevant Documentation:\n\n";
        
        foreach ($results['devdocs'] as $result) {
            $context .= "[DEVDOCS] {$result['title']}\n";
            $context .= "File: {$result['file']}\n";
            $context .= "Content: {$result['content']}\n\n";
        }
        
        foreach ($results['phpdocs'] as $result) {
            $context .= "[PHPDOCS] {$result['title']}\n";
            $context .= "File: {$result['file']}\n";
            $context .= "Content: {$result['content']}\n\n";
        }
        
        return $context;
    }
}

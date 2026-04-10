<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;

/**
 * Handles static informational pages.
 *
 * These serve as progressive-enhancement fallbacks for the <dialog> modals:
 * without JavaScript, nav links like /how-to and /faq navigate here instead.
 * With JS, the same content is shown inside a modal — both share content
 * partials (content-how-to.php, content-faq.php) to avoid duplication.
 */
class PageController extends BaseController
{
    /**
     * Render the standalone How It Works page.
     */
    public function howTo(): void
    {
        $this->render('pages/how-to', [
            'title'       => 'How It Works — NeighborhoodTools',
            'description' => 'Learn how to borrow and lend tools with NeighborhoodTools — for borrowers, lenders, and community safety.',
            'pageCss'     => ['pages.css'],
        ]);
    }

    /**
     * Render the standalone FAQs page.
     */
    public function faq(): void
    {
        $this->render('pages/faq', [
            'title'       => 'FAQs — NeighborhoodTools',
            'description' => 'Frequently asked questions about using NeighborhoodTools — accounts, deposits, disputes, ratings, and more.',
            'pageCss'     => ['pages.css'],
        ]);
    }

    /**
     * Render the custom 403 error page for Apache ErrorDocument.
     */
    public function forbidden(): never
    {
        $this->abort(403);
    }

    /**
     * Render the custom 404 error page for Apache ErrorDocument.
     */
    public function notFound(): never
    {
        $this->abort(404);
    }

    /**
     * Render the custom 500 error page for Apache ErrorDocument.
     */
    public function serverError(): never
    {
        $this->abort(500);
    }

    /**
     * Generate an XML sitemap for search-engine crawlers.
     */
    public function sitemap(): void
    {
        $appConfig = require BASE_PATH . '/config/app.php';
        $base = rtrim($appConfig['url'], '/');

        $static = [
            ['loc' => '/',          'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/tools',     'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => '/available', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => '/events',    'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/how-to',    'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/faq',       'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/tos',       'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => '/login',     'priority' => '0.4', 'changefreq' => 'yearly'],
            ['loc' => '/register',  'priority' => '0.5', 'changefreq' => 'yearly'],
        ];

        $pdo = Database::connection();

        $toolRows = $pdo->query(
            "SELECT id_tol AS id, updated_at_tol AS updated_at
             FROM tool_tol
             WHERE is_available_tol = 1 AND is_deleted_tol = 0
             ORDER BY id_tol"
        )->fetchAll();

        $eventRows = $pdo->query(
            'SELECT id_evt AS id, updated_at_evt AS updated_at FROM event_evt ORDER BY id_evt'
        )->fetchAll();

        $profileRows = $pdo->query(
            "SELECT id_acc AS id, updated_at_acc AS updated_at
             FROM account_acc
             WHERE id_ast_acc = (SELECT id_ast FROM account_status_ast WHERE status_name_ast = 'active')
             ORDER BY id_acc"
        )->fetchAll();

        header('Content-Type: application/xml; charset=utf-8');

        $xml = new \XMLWriter();
        $xml->openURI('php://output');
        $xml->startDocument('1.0', 'utf-8');
        $xml->setIndent(true);

        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($static as $page) {
            $xml->startElement('url');
            $xml->writeElement('loc', $base . $page['loc']);
            $xml->writeElement('changefreq', $page['changefreq']);
            $xml->writeElement('priority', $page['priority']);
            $xml->endElement();
        }

        foreach ($toolRows as $row) {
            $xml->startElement('url');
            $xml->writeElement('loc', $base . '/tools/' . $row['id']);
            $xml->writeElement('lastmod', date('Y-m-d', strtotime($row['updated_at'])));
            $xml->writeElement('changefreq', 'weekly');
            $xml->writeElement('priority', '0.7');
            $xml->endElement();
        }

        foreach ($eventRows as $row) {
            $xml->startElement('url');
            $xml->writeElement('loc', $base . '/events/' . $row['id']);
            $xml->writeElement('lastmod', date('Y-m-d', strtotime($row['updated_at'])));
            $xml->writeElement('changefreq', 'weekly');
            $xml->writeElement('priority', '0.6');
            $xml->endElement();
        }

        foreach ($profileRows as $row) {
            $xml->startElement('url');
            $xml->writeElement('loc', $base . '/profile/' . $row['id']);
            $xml->writeElement('lastmod', date('Y-m-d', strtotime($row['updated_at'])));
            $xml->writeElement('changefreq', 'monthly');
            $xml->writeElement('priority', '0.5');
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
        exit;
    }
}

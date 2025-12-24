<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\AiGeneration;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\Tag;
use App\Entity\WooImportLog;
use App\Entity\AiProviderConfig;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Auramur CMS')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        yield MenuItem::section('Contenu');
        yield MenuItem::linkToCrud('Produits', 'fa fa-shopping-bag', Product::class);
        yield MenuItem::linkToCrud('Variantes', 'fa fa-list', ProductVariant::class);
        yield MenuItem::linkToCrud('Articles', 'fa fa-newspaper', Article::class);

        yield MenuItem::section('Organisation');
        yield MenuItem::linkToCrud('Catégories', 'fa fa-folder', Category::class);
        yield MenuItem::linkToCrud('Tags', 'fa fa-tags', Tag::class);

        yield MenuItem::section('IA & Imports');
        yield MenuItem::linkToCrud('Configurations IA', 'fa fa-robot', AiProviderConfig::class);
        yield MenuItem::linkToCrud('Générations IA', 'fa fa-brain', AiGeneration::class);
        yield MenuItem::linkToCrud('Logs d\'import WooCommerce', 'fa fa-wordpress', WooImportLog::class);

        // yield MenuItem::section('Outils');
        // yield MenuItem::linkToRoute('Import WooCommerce', 'fa fa-upload', 'admin_import_woo');
        // yield MenuItem::linkToRoute('Export Typesense', 'fa fa-download', 'admin_export_typesense');
    }
}

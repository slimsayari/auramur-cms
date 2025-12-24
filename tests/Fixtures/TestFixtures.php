<?php

namespace App\Tests\Fixtures;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Entity\ProductImage;
use App\Entity\ProductSeo;
use App\Entity\Article;
use App\Entity\ArticleSeo;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\User;
use App\Enum\ContentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@test.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Créer des catégories
        $categories = [];
        $categoryNames = ['Tropical', 'Moderne', 'Vintage', 'Minimaliste'];
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug(strtolower($name));
            $manager->persist($category);
            $categories[] = $category;
        }

        // Créer des tags
        $tags = [];
        $tagNames = ['Populaire', 'Nouveau', 'Promo', 'Écologique'];
        foreach ($tagNames as $name) {
            $tag = new Tag();
            $tag->setName($name);
            $tag->setSlug(strtolower($name));
            $manager->persist($tag);
            $tags[] = $tag;
        }

        // Créer des produits
        for ($i = 1; $i <= 10; $i++) {
            $product = new Product();
            $product->setName("Papier Peint Test {$i}");
            $product->setSlug("papier-peint-test-{$i}");
            $product->setDescription("Description du produit test {$i}");
            $product->setSku("TEST-SKU-{$i}");
            $product->setPrice(99.99 + $i);
            $product->setStatus($i <= 5 ? ContentStatus::PUBLISHED : ContentStatus::DRAFT);

            // Ajouter des catégories
            $product->addCategory($categories[array_rand($categories)]);
            $product->addCategory($categories[array_rand($categories)]);

            // Ajouter des tags
            $product->addTag($tags[array_rand($tags)]);

            // Ajouter des variantes
            for ($v = 1; $v <= 3; $v++) {
                $variant = new ProductVariant();
                $variant->setProduct($product);
                $variant->setSku("TEST-SKU-{$i}-VAR-{$v}");
                $variant->setName("Variante {$v}");
                $variant->setDimensions("100x" . (200 + $v * 50));
                $variant->setPricePerM2(4.99 + $v);
                $variant->setStock(10 + $v * 5);
                $variant->setIsActive(true);
                $product->addVariant($variant);
                $manager->persist($variant);
            }

            // Ajouter des images
            for ($img = 1; $img <= 2; $img++) {
                $image = new ProductImage();
                $image->setProduct($product);
                $image->setUrl("https://via.placeholder.com/1920x1080?text=Product+{$i}+Image+{$img}");
                $image->setAltText("Image {$img} du produit {$i}");
                $image->setFormat('jpg');
                $image->setDpi(300);
                $image->setWidth(1920);
                $image->setHeight(1080);
                $image->setPosition($img);
                $product->addImage($image);
                $manager->persist($image);
            }

            // Ajouter le SEO
            $seo = new ProductSeo();
            $seo->setProduct($product);
            $seo->setSeoTitle("Papier Peint Test {$i} - Achat en ligne");
            $seo->setMetaDescription("Découvrez notre papier peint test {$i}. Qualité premium, livraison rapide.");
            $seo->setSlug($product->getSlug());
            $seo->setNoindex(false);
            $seo->setNofollow(false);
            $seo->setSchemaReady(true);
            $product->setSeo($seo);
            $manager->persist($seo);

            $manager->persist($product);
        }

        // Créer des articles
        for ($i = 1; $i <= 5; $i++) {
            $article = new Article();
            $article->setTitle("Article Test {$i}");
            $article->setSlug("article-test-{$i}");
            $article->setContent("Contenu complet de l'article test {$i}. Lorem ipsum dolor sit amet...");
            $article->setExcerpt("Extrait de l'article test {$i}");
            $article->setStatus($i <= 3 ? ContentStatus::PUBLISHED : ContentStatus::DRAFT);

            // Ajouter le SEO
            $seo = new ArticleSeo();
            $seo->setArticle($article);
            $seo->setSeoTitle("Article Test {$i} - Blog Auramur");
            $seo->setMetaDescription("Lisez notre article test {$i} sur les tendances déco.");
            $seo->setSlug($article->getSlug());
            $article->setSeo($seo);
            $manager->persist($seo);

            $manager->persist($article);
        }

        $manager->flush();
    }
}

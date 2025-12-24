<?php

namespace App\Controller\Admin;

use App\Entity\AiProviderConfig;
use App\Service\Ai\AiImageGeneratorFactory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AiProviderConfigCrudController extends AbstractCrudController
{
    public function __construct(
        private AiImageGeneratorFactory $factory
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AiProviderConfig::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Configuration IA')
            ->setEntityLabelInPlural('Configurations IA')
            ->setPageTitle('index', 'Configurations IA')
            ->setPageTitle('new', 'Nouvelle configuration IA')
            ->setPageTitle('edit', 'Modifier la configuration IA')
            ->setHelp('index', 'Gérez les providers de génération d\'images IA (NanoBanana, Midjourney, etc.). Une seule configuration peut être active à la fois.')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit')->setLabel('Modifier');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel('Supprimer');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Nom')
            ->setHelp('Nom descriptif de cette configuration (ex: "NanoBanana Production")')
            ->setRequired(true);

        yield ChoiceField::new('provider', 'Provider')
            ->setChoices([
                'NanoBanana' => 'nanobanana',
                'Midjourney' => 'midjourney',
                'Stable Diffusion' => 'stable_diffusion',
            ])
            ->setHelp('Provider de génération d\'images IA')
            ->setRequired(true);

        yield TextField::new('apiKey', 'Clé API')
            ->setHelp('Clé API du provider (sera chiffrée automatiquement)')
            ->setRequired(true)
            ->onlyOnForms()
            ->setFormTypeOption('attr', ['type' => 'password']);

        yield TextField::new('apiSecret', 'Secret API')
            ->setHelp('Secret API (optionnel, sera chiffré automatiquement)')
            ->onlyOnForms()
            ->setFormTypeOption('attr', ['type' => 'password']);

        yield TextField::new('model', 'Modèle')
            ->setHelp('Modèle à utiliser (ex: "flux-pro", "midjourney-v6")')
            ->setRequired(false);

        yield ChoiceField::new('imageSize', 'Taille d\'image')
            ->setChoices([
                '512x512' => '512x512',
                '768x768' => '768x768',
                '1024x1024' => '1024x1024',
                '1024x1792' => '1024x1792',
                '1792x1024' => '1792x1024',
            ])
            ->setHelp('Taille par défaut des images générées')
            ->setRequired(false);

        yield TextareaField::new('defaultPrompt', 'Prompt par défaut')
            ->setHelp('Prompt de base qui sera ajouté à tous les prompts (ex: "high quality, professional, 4k")')
            ->setRequired(false)
            ->hideOnIndex();

        yield BooleanField::new('isActive', 'Actif')
            ->setHelp('Une seule configuration peut être active à la fois');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->hideOnForm();
    }

    /**
     * Chiffre la clé API avant la persistance
     */
    public function persistEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof AiProviderConfig) {
            // Chiffrer la clé API
            $apiKey = $entityInstance->getApiKey();
            $encryptedApiKey = $this->factory->encrypt($apiKey);
            $entityInstance->setApiKey($encryptedApiKey);

            // Chiffrer le secret API si présent
            if ($entityInstance->getApiSecret()) {
                $apiSecret = $entityInstance->getApiSecret();
                $encryptedApiSecret = $this->factory->encrypt($apiSecret);
                $entityInstance->setApiSecret($encryptedApiSecret);
            }

            // Désactiver les autres configurations si celle-ci est active
            if ($entityInstance->isActive()) {
                $this->deactivateOtherConfigs($entityManager);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Chiffre la clé API avant la mise à jour
     */
    public function updateEntity($entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof AiProviderConfig) {
            // Si la clé API a été modifiée, la chiffrer
            $apiKey = $entityInstance->getApiKey();
            if (!$this->isEncrypted($apiKey)) {
                $encryptedApiKey = $this->factory->encrypt($apiKey);
                $entityInstance->setApiKey($encryptedApiKey);
            }

            // Si le secret API a été modifié, le chiffrer
            if ($entityInstance->getApiSecret() && !$this->isEncrypted($entityInstance->getApiSecret())) {
                $apiSecret = $entityInstance->getApiSecret();
                $encryptedApiSecret = $this->factory->encrypt($apiSecret);
                $entityInstance->setApiSecret($encryptedApiSecret);
            }

            // Désactiver les autres configurations si celle-ci est active
            if ($entityInstance->isActive()) {
                $this->deactivateOtherConfigs($entityManager, $entityInstance->getId());
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Désactive toutes les autres configurations
     */
    private function deactivateOtherConfigs($entityManager, $excludeId = null): void
    {
        $qb = $entityManager->createQueryBuilder();
        $qb->update(AiProviderConfig::class, 'a')
            ->set('a.isActive', ':false')
            ->setParameter('false', false);

        if ($excludeId) {
            $qb->where('a.id != :id')
                ->setParameter('id', $excludeId);
        }

        $qb->getQuery()->execute();
    }

    /**
     * Vérifie si une valeur est déjà chiffrée
     */
    private function isEncrypted(string $value): bool
    {
        // Une valeur chiffrée est en base64 et commence généralement par un pattern reconnaissable
        return base64_encode(base64_decode($value, true)) === $value && strlen($value) > 50;
    }
}

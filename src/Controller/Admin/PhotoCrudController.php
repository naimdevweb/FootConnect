<?php
// filepath: /c:/Users/Dev404/Documents/foot_connect/FOOT_CONNECT/src/Controller/Admin/PhotoCrudController.php

namespace App\Controller\Admin;

use App\Entity\Photo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

/**
 * Contrôleur CRUD pour gérer les photos dans l'interface d'administration
 */
class PhotoCrudController extends AbstractCrudController
{
    /**
     * Retourne le FQCN de l'entité gérée par ce contrôleur
     */
    public static function getEntityFqcn(): string
    {
        return Photo::class;
    }

    /**
     * Configure les champs affichés dans les différentes pages
     */
    public function configureFields(string $pageName): iterable
    {
        IdField::new('id')->hideOnForm();
            
        yield AssociationField::new('user')
            ->setLabel('Utilisateur');
            
            
            
        yield ImageField::new('photoUrl')
            ->setLabel('Image')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->onlyOnIndex();
            
        yield ImageField::new('photoUrl')
            ->setLabel('Image')
            ->setBasePath('uploads/photos')
            ->setUploadDir('public/uploads/photos')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->hideOnIndex();
            
        yield TextareaField::new('description')
            ->setLabel('Description')
            ->hideOnIndex();
            
        yield IntegerField::new('likes', 'Nombre de likes')
            ->formatValue(function ($value, $entity) {
                return count($entity->getLikes());
            })
            ->onlyOnIndex();
            
        yield DateTimeField::new('createdAt')
            ->setLabel('Date de publication')
            ->setFormat('dd/MM/yyyy HH:mm');
    }
    
    /**
     * Configure les options générales du CRUD
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo')
            ->setEntityLabelInPlural('Photos')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPageTitle('index', 'Gestion des photos')
            ->setPageTitle('detail', fn (Photo $photo) => sprintf('Photo #%s', $photo->getId()));
    }
    
    /**
     * Configure les actions disponibles sur l'entité
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash')->setLabel('Supprimer');
            });
    }
}
<?php declare(strict_types=1);

namespace Swag\CustomMediaResolver\Extension\Content\Product;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Runtime;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        // Oryginalne pole dla pojedynczego medium
        $collection->add(
            (new OneToOneAssociationField(
                'customMediaObject', // Nazwa naszego nowego pola w API
                'custom_fields.custom_product_data_more_about_product_media', // Klucz ID w bazie danych (wewnątrz JSON)
                'id', // Klucz w encji Media, z którym łączymy
                MediaDefinition::class, // Klasa definicji docelowej encji
                false // Czy asocjacja ma być aut ładowana (false)
            ))->addFlags(new Runtime(), new ApiAware())
        );

        // Nowe pole dla wielu mediów klienta
        $collection->add(
            (new OneToManyAssociationField(
                'customClientMediaArray', // Nazwa naszego nowego pola w API
                MediaDefinition::class, // Klasa definicji docelowej encji
                'id', // Klucz w encji Media, z którym łączymy
                'product_id', // Referencja do produktu (nie używana w tym przypadku)
                false // Czy asocjacja ma być aut ładowana (false)
            ))->addFlags(new Runtime(), new ApiAware())
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function getEntityName(): string
    {
        return 'product';
    }
}
<?php declare(strict_types=1);

namespace Swag\CustomMediaResolver\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;

class ProductSubscriber implements EventSubscriberInterface
{
    // Upewnij się, że ta nazwa jest identyczna z techniczną nazwą pola w Twoim Shopware
    private const CUSTOM_FIELD_TECHNICAL_NAME = 'custom_product_data_more_about_product_media';
    
    // Nowe pole dla mediów klienta
    private const CUSTOM_CLIENT_MEDIA_PREFIX = 'custom_product_client_media_';
    private const MAX_CLIENT_MEDIA_COUNT = 12;

    private EntityRepository $mediaRepository;

    public function __construct(EntityRepository $mediaRepository)
    {
        $this->mediaRepository = $mediaRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_LOADED_EVENT => 'onProductLoaded',
        ];
    }

    public function onProductLoaded(EntityLoadedEvent $event): void
    {
        /** @var SalesChannelProductEntity $product */
        foreach ($event->getEntities() as $product) {
            // Pobieramy przetłumaczone pola niestandardowe
            $translatedCustomFields = $product->getTranslated()['customFields'] ?? null;

            // Jeśli nie ma pól niestandardowych lub są puste, przechodzimy do następnego produktu
            if (empty($translatedCustomFields)) {
                continue;
            }
            
            // Obsługa oryginalnego pola - pojedyncze medium
            $this->handleSingleMedia($product, $translatedCustomFields, $event->getContext());
            
            // Obsługa nowego pola - wiele mediów klienta
            $this->handleClientMedia($product, $translatedCustomFields, $event->getContext());
        }
    }

    private function handleSingleMedia($product, array $translatedCustomFields, $context): void
    {
        // Szukamy ID medium w przetłumaczonych polach
        $mediaId = $translatedCustomFields[self::CUSTOM_FIELD_TECHNICAL_NAME] ?? null;
        
        // Jeśli nie znaleziono ID, przechodzimy dalej
        if ($mediaId === null) {
            return;
        }

        // Pobieramy pełny obiekt media z bazy danych
        $mediaObject = $this->mediaRepository->search(new Criteria([$mediaId]), $context)->first();
        
        // Jeśli obiekt media istnieje, dodajemy go jako rozszerzenie do produktu
        if ($mediaObject) {
            $product->addExtension('customMediaObject', $mediaObject);
        }
    }

    private function handleClientMedia($product, array $translatedCustomFields, $context): void
    {
        $clientMediaIds = [];
        
        // Sprawdzamy wszystkie pola custom_product_client_media_1 do custom_product_client_media_8
        for ($i = 1; $i <= self::MAX_CLIENT_MEDIA_COUNT; $i++) {
            $fieldName = self::CUSTOM_CLIENT_MEDIA_PREFIX . $i;
            $mediaId = $translatedCustomFields[$fieldName] ?? null;
            
            if ($mediaId !== null) {
                $clientMediaIds[] = $mediaId;
            }
        }
        
        // Jeśli nie znaleziono żadnych mediów klienta, kończymy
        if (empty($clientMediaIds)) {
            return;
        }

        // Pobieramy wszystkie obiekty media z bazy danych
        $criteria = new Criteria($clientMediaIds);
        $mediaObjects = $this->mediaRepository->search($criteria, $context)->getElements();
        
        // Jeśli znaleziono obiekty media, dodajemy je jako rozszerzenie do produktu
        if (!empty($mediaObjects)) {
            $product->addExtension('customClientMediaArray', new ArrayStruct($mediaObjects));
        }
    }
}
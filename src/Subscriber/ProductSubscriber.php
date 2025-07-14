<?php declare(strict_types=1);

namespace Swag\CustomMediaResolver\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Product\ProductEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;

class ProductSubscriber implements EventSubscriberInterface
{
    // Upewnij się, że ta nazwa jest identyczna z techniczną nazwą pola w Twoim Shopware
    private const CUSTOM_FIELD_TECHNICAL_NAME = 'custom_product_data_more_about_product_media';

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
            // ---- POCZĄTEK ZMIAN ----
            // Pobieramy przetłumaczone pola niestandardowe
            $translatedCustomFields = $product->getTranslated()['customFields'] ?? null;

            // Jeśli nie ma pól niestandardowych lub są puste, przechodzimy do następnego produktu
            if (empty($translatedCustomFields)) {
                continue;
            }
            
            // Szukamy ID medium w przetłumaczonych polach
            $mediaId = $translatedCustomFields[self::CUSTOM_FIELD_TECHNICAL_NAME] ?? null;
            // ---- KONIEC ZMIAN ----
            
            // Jeśli nie znaleziono ID, przechodzimy dalej
            if ($mediaId === null) {
                continue;
            }

            // Pobieramy pełny obiekt media z bazy danych
            $context = $event->getContext();
            $mediaObject = $this->mediaRepository->search(new Criteria([$mediaId]), $context)->first();
            
            // Jeśli obiekt media istnieje, dodajemy go jako rozszerzenie do produktu
            if ($mediaObject) {
                $product->addExtension('customMediaObject', $mediaObject);
            }
        }
    }
}
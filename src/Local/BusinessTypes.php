<?php

declare(strict_types=1);

namespace Saazify\LaravelSeo\Local;

final class BusinessTypes
{
    /**
     * Common schema.org types. Unknown values fall back to Organization.
     *
     * @var list<string>
     */
    public const TYPES = [
        'Organization',
        'LocalBusiness',
        'AnimalShelter',
        'AutomotiveBusiness',
        'ChildCare',
        'Dentist',
        'DryCleaningOrLaundry',
        'EmergencyService',
        'EmploymentAgency',
        'EntertainmentBusiness',
        'FinancialService',
        'FoodEstablishment',
        'GovernmentOffice',
        'HealthAndBeautyBusiness',
        'HomeAndConstructionBusiness',
        'InternetCafe',
        'LegalService',
        'Library',
        'LodgingBusiness',
        'MedicalBusiness',
        'ProfessionalService',
        'RadioStation',
        'RealEstateAgent',
        'RecyclingCenter',
        'SelfStorage',
        'ShoppingCenter',
        'SportsActivityLocation',
        'Store',
        'TelevisionStation',
        'TouristInformationCenter',
        'TravelAgency',
        'Restaurant',
        'Bakery',
        'BarOrPub',
        'CafeOrCoffeeShop',
        'FastFoodRestaurant',
        'IceCreamShop',
        'ClothingStore',
        'ConvenienceStore',
        'ElectronicsStore',
        'Florist',
        'FurnitureStore',
        'GroceryStore',
        'HardwareStore',
        'HobbyShop',
        'JewelryStore',
        'LiquorStore',
        'MensClothingStore',
        'MobilePhoneStore',
        'MovieRentalStore',
        'MusicStore',
        'OfficeEquipmentStore',
        'OutletStore',
        'PawnShop',
        'PetStore',
        'ShoeStore',
        'SportingGoodsStore',
        'TireShop',
        'ToyStore',
        'WholesaleStore',
        'Hospital',
        'Pharmacy',
        'Physician',
        'VeterinaryCare',
        'Hotel',
        'BedAndBreakfast',
        'Hostel',
        'Motel',
        'School',
        'CollegeOrUniversity',
        'HighSchool',
        'Preschool',
    ];

    public static function normalize(string $type): string
    {
        return in_array($type, self::TYPES, true) ? $type : 'Organization';
    }
}

from typing import Dict
import datetime

# =============================================================================
# DYNAMIC PRICING - SURGE PRICING ALGORITHM
# =============================================================================

class PricingEngine:
    def __init__(self, base_price: float = 10.0):
        self.base_price = base_price
        self.demand_history = []
        self.supply_history = []
    
    def calculate_surge_multiplier(self, current_demand: int, available_drivers: int, 
                                 time_of_day: int, weather_factor: float = 1.0,
                                 special_event: bool = False) -> float:
        """
        Calculate dynamic pricing multiplier based on multiple factors
        
        Args:
            current_demand: Number of ride requests in the area
            available_drivers: Number of available drivers in the area
            time_of_day: Hour of day (0-23)
            weather_factor: Weather multiplier (1.0 = normal, >1.0 = bad weather)
            special_event: Whether there's a special event nearby
        
        Returns:
            Surge multiplier (1.0 = normal price)
        """
        # Base surge calculation: demand vs supply ratio
        if available_drivers == 0:
            supply_demand_ratio = float('inf')
        else:
            supply_demand_ratio = current_demand / available_drivers
        
        # Time-based multiplier
        time_multiplier = self._get_time_multiplier(time_of_day)
        
        # Calculate base surge from supply/demand
        surge_base = self._calculate_demand_surge(supply_demand_ratio)
        
        # Special event multiplier
        event_multiplier = 1.4 if special_event else 1.0
        
        # Apply all multipliers
        final_multiplier = surge_base * time_multiplier * weather_factor * event_multiplier
        
        # Cap the maximum surge to prevent excessive pricing
        return min(final_multiplier, 5.0)
    
    def _get_time_multiplier(self, time_of_day: int) -> float:
        """Calculate time-based pricing multiplier"""
        if 7 <= time_of_day <= 9:  # Morning rush hour
            return 1.3
        elif 17 <= time_of_day <= 19:  # Evening rush hour
            return 1.4
        elif 22 <= time_of_day or time_of_day <= 5:  # Late night/early morning
            return 1.2
        elif 12 <= time_of_day <= 14:  # Lunch time
            return 1.1
        else:  # Normal hours
            return 1.0
    
    def _calculate_demand_surge(self, supply_demand_ratio: float) -> float:
        """Calculate surge multiplier based on supply/demand ratio"""
        if supply_demand_ratio <= 0.8:  # More drivers than demand
            return 0.9  # Slight discount to attract customers
        elif supply_demand_ratio <= 1.0:  # Balanced
            return 1.0
        elif supply_demand_ratio <= 1.5:  # Mild demand
            return 1.2
        elif supply_demand_ratio <= 2.0:  # Moderate demand
            return 1.5
        elif supply_demand_ratio <= 3.0:  # High demand
            return 2.0
        elif supply_demand_ratio <= 5.0:  # Very high demand
            return 2.5
        else:  # Extreme demand
            return min(3.5, 1.0 + (supply_demand_ratio - 1) * 0.4)
    
    def get_dynamic_price(self, distance: float, duration: float, 
                         current_demand: int, available_drivers: int,
                         time_of_day: int, weather_factor: float = 1.0,
                         special_event: bool = False,
                         vehicle_type: str = "standard") -> Dict:
        """
        Calculate final dynamic price for a trip
        
        Args:
            distance: Trip distance in kilometers
            duration: Estimated trip duration in minutes
            vehicle_type: "standard", "premium", "luxury"
        """
        # Vehicle type multipliers
        vehicle_multipliers = {
            "standard": 1.0,
            "premium": 1.4,
            "luxury": 2.0
        }
        
        vehicle_multiplier = vehicle_multipliers.get(vehicle_type, 1.0)
        
        # Calculate surge multiplier
        surge_multiplier = self.calculate_surge_multiplier(
            current_demand, available_drivers, time_of_day, 
            weather_factor, special_event
        )
        
        # Base price calculation (distance + time + base fare)
        distance_cost = distance * 1.5 * vehicle_multiplier  # Per km rate
        time_cost = duration * 0.25 * vehicle_multiplier     # Per minute rate
        base_total = self.base_price + distance_cost + time_cost
        
        # Apply surge
        final_price = base_total * surge_multiplier
        
        # Calculate breakdown
        return {
            'base_fare': self.base_price,
            'distance_cost': round(distance_cost, 2),
            'time_cost': round(time_cost, 2),
            'base_total': round(base_total, 2),
            'vehicle_type': vehicle_type,
            'vehicle_multiplier': vehicle_multiplier,
            'surge_multiplier': round(surge_multiplier, 2),
            'final_price': round(final_price, 2),
            'surge_amount': round(final_price - base_total, 2),
            'savings': round(base_total - final_price, 2) if surge_multiplier < 1.0 else 0
        }
    
    def get_pricing_explanation(self, pricing_data: Dict) -> str:
        """Generate human-readable explanation of pricing"""
        explanation = []
        
        if pricing_data['surge_multiplier'] > 1.0:
            explanation.append(f"Higher demand in your area (+{pricing_data['surge_multiplier']}x)")
        elif pricing_data['surge_multiplier'] < 1.0:
            explanation.append(f"Lower demand in your area (-{abs(pricing_data['surge_multiplier'] - 1.0):.1f}x)")
        
        if pricing_data['vehicle_multiplier'] > 1.0:
            explanation.append(f"{pricing_data['vehicle_type'].title()} vehicle selected")
        
        if not explanation:
            explanation.append("Standard pricing")
            
        return " • ".join(explanation)

# =============================================================================
# DEMONSTRATION AND TESTING
# =============================================================================

def demonstrate_surge_pricing():
    print("=== DYNAMIC SURGE PRICING DEMONSTRATION ===\n")
    
    pricing_engine = PricingEngine()
    
    # Test scenarios with different conditions
    scenarios = [
        {
            "name": "Normal Morning",
            "demand": 8, "drivers": 12, "time": 10, "weather": 1.0, 
            "event": False, "vehicle": "standard"
        },
        {
            "name": "Rush Hour Peak",
            "demand": 25, "drivers": 8, "time": 18, "weather": 1.0, 
            "event": False, "vehicle": "standard"
        },
        {
            "name": "Rainy Night",
            "demand": 30, "drivers": 5, "time": 23, "weather": 1.6, 
            "event": False, "vehicle": "standard"
        },
        {
            "name": "Concert Event",
            "demand": 45, "drivers": 10, "time": 22, "weather": 1.0, 
            "event": True, "vehicle": "premium"
        },
        {
            "name": "Low Demand Period",
            "demand": 3, "drivers": 15, "time": 14, "weather": 1.0, 
            "event": False, "vehicle": "standard"
        },
        {
            "name": "Luxury Ride",
            "demand": 12, "drivers": 8, "time": 19, "weather": 1.0, 
            "event": False, "vehicle": "luxury"
        }
    ]
    
    # Standard trip: 8km, 20 minutes
    trip_distance = 8.0
    trip_duration = 20.0
    
    print(f"Trip Details: {trip_distance}km, {trip_duration} minutes\n")
    print("=" * 80)
    
    for scenario in scenarios:
        price_data = pricing_engine.get_dynamic_price(
            distance=trip_distance,
            duration=trip_duration,
            current_demand=scenario["demand"],
            available_drivers=scenario["drivers"],
            time_of_day=scenario["time"],
            weather_factor=scenario["weather"],
            special_event=scenario["event"],
            vehicle_type=scenario["vehicle"]
        )
        
        explanation = pricing_engine.get_pricing_explanation(price_data)
        
        print(f"📍 {scenario['name']}")
        print(f"   Conditions: {scenario['demand']} requests, {scenario['drivers']} drivers")
        print(f"   Base fare: ${price_data['base_fare']}")
        print(f"   Distance: ${price_data['distance_cost']} ({trip_distance}km)")
        print(f"   Time: ${price_data['time_cost']} ({trip_duration}min)")
        print(f"   Subtotal: ${price_data['base_total']}")
        
        if price_data['surge_multiplier'] != 1.0:
            if price_data['surge_multiplier'] > 1.0:
                print(f"   🔴 Surge: +${price_data['surge_amount']} ({price_data['surge_multiplier']}x)")
            else:
                print(f"   🟢 Discount: -${abs(price_data['surge_amount'])} ({price_data['surge_multiplier']}x)")
        
        print(f"   💰 TOTAL: ${price_data['final_price']}")
        print(f"   📝 {explanation}")
        print()
    
    # Show time-based pricing throughout the day
    print("=" * 80)
    print("⏰ HOURLY PRICING VARIATIONS (Standard conditions: 10 requests, 8 drivers)")
    print("=" * 80)
    
    hourly_data = []
    for hour in range(0, 24, 2):  # Every 2 hours
        price_data = pricing_engine.get_dynamic_price(
            distance=5.0, duration=15.0,
            current_demand=10, available_drivers=8,
            time_of_day=hour, weather_factor=1.0
        )
        
        time_label = f"{hour:02d}:00"
        if 6 <= hour <= 11:
            period = "Morning"
        elif 12 <= hour <= 17:
            period = "Afternoon" 
        elif 18 <= hour <= 22:
            period = "Evening"
        else:
            period = "Night"
            
        print(f"{time_label} ({period:9s}): ${price_data['final_price']:6.2f} "
              f"(Base: ${price_data['base_total']:6.2f}, "
              f"Surge: {price_data['surge_multiplier']:4.1f}x)")

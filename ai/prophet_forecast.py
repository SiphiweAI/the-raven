#!/usr/bin/env python3
"""
AI Forecasting using Facebook Prophet
Generates demand forecasts based on historical sales data
"""

import sys
import json
from pathlib import Path
from datetime import datetime
import pandas as pd

try:
    from prophet import Prophet
except ImportError:
    print("ERROR: Prophet library not installed. Run: pip install prophet", file=sys.stderr)
    sys.exit(1)

def main():
    """Main forecasting function"""
    
    # Configuration
    PRODUCT_ID = "P001"
    FORECAST_PERIODS = 7  # Days to forecast
    INPUT_FILE = "sales_export.json"
    OUTPUT_FILE = f"forecast_{PRODUCT_ID}.json"
    
    try:
        # Load sales data
        if not Path(INPUT_FILE).exists():
            raise FileNotFoundError(f"Input file not found: {INPUT_FILE}")
        
        with open(INPUT_FILE, 'r') as f:
            sales = json.load(f)
        
        if not sales:
            raise ValueError("No sales data in input file")
        
        print(f"Loaded {len(sales)} sales records")
        
        # Filter for specific product
        product_sales = [s for s in sales if s.get('product_id') == PRODUCT_ID]
        
        if not product_sales:
            raise ValueError(f"No sales data found for product {PRODUCT_ID}")
        
        print(f"Found {len(product_sales)} records for product {PRODUCT_ID}")
        
        # Create DataFrame
        df = pd.DataFrame(product_sales)
        
        # Validate required columns
        if 'ds' not in df.columns or 'y' not in df.columns:
            raise ValueError("Sales data must contain 'ds' (date) and 'y' (quantity) columns")
        
        # Convert data types
        df['ds'] = pd.to_datetime(df['ds'])
        df['y'] = pd.to_numeric(df['y'], errors='coerce')
        
        # Remove any NaN values
        df = df.dropna(subset=['ds', 'y'])
        
        if len(df) < 2:
            raise ValueError("Insufficient data points for forecasting (minimum 2 required)")
        
        print(f"Prepared {len(df)} valid data points")
        
        # Sort by date
        df = df.sort_values('ds').reset_index(drop=True)
        
        # Initialize Prophet model
        model = Prophet(
            daily_seasonality=False,
            weekly_seasonality=True,
            yearly_seasonality=False,
            changepoint_prior_scale=0.05,  # Flexibility of trend
            seasonality_prior_scale=10.0    # Flexibility of seasonality
        )
        
        print("Training Prophet model...")
        
        # Fit model
        model.fit(df)
        
        print("Model trained successfully")
        
        # Create future dataframe
        future = model.make_future_dataframe(periods=FORECAST_PERIODS)
        
        # Generate forecast
        forecast = model.predict(future)
        
        print(f"Generated forecast for {FORECAST_PERIODS} periods")
        
        # Extract forecast data (only future periods)
        forecast_output = forecast[['ds', 'yhat', 'yhat_lower', 'yhat_upper']].tail(FORECAST_PERIODS)
        
        # Convert to JSON-serializable format
        forecast_dict = []
        for _, row in forecast_output.iterrows():
            forecast_dict.append({
                'ds': row['ds'].strftime('%Y-%m-%d'),
                'yhat': float(max(0, row['yhat'])),  # Ensure non-negative
                'yhat_lower': float(max(0, row['yhat_lower'])),
                'yhat_upper': float(max(0, row['yhat_upper']))
            })
        
        # Save forecast
        with open(OUTPUT_FILE, 'w') as f:
            json.dump(forecast_dict, f, indent=2)
        
        print(f"Forecast saved to {OUTPUT_FILE}")
        
        # Print summary
        print("\nForecast Summary:")
        for item in forecast_dict:
            print(f"  {item['ds']}: {item['yhat']:.2f} units "
                  f"(range: {item['yhat_lower']:.2f} - {item['yhat_upper']:.2f})")
        
        return 0
        
    except FileNotFoundError as e:
        print(f"ERROR: {e}", file=sys.stderr)
        return 1
    except ValueError as e:
        print(f"ERROR: {e}", file=sys.stderr)
        return 1
    except Exception as e:
        print(f"ERROR: Unexpected error: {e}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        return 1

if __name__ == "__main__":
    sys.exit(main())
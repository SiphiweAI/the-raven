import pandas as pd
from prophet import Prophet
import json
from datetime import datetime


# Load data exported from WP
with open('sales_export.json', 'r') as f:
    sales = json.load(f)


# Filter for one product
product_id = "P001"
df = pd.DataFrame([s for s in sales if s['product_id'] == product_id])


# Prophet expects columns ds (date) and y (value)
df['ds'] = pd.to_datetime(df['ds'])
df['y'] = df['y'].astype(float)


# Fit model
model = Prophet()
model.fit(df)


# Forecast next 7 days
future = model.make_future_dataframe(periods=7)
forecast = model.predict(future)


# Save forecast as JSON
forecast_json = forecast[['ds', 'yhat']].tail(7).to_dict(orient='records')
with open(f'forecast_{product_id}.json', 'w') as f:
    json.dump(forecast_json, f)

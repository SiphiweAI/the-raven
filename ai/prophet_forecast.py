import pandas as pd
from prophet import Prophet
import json
import sys

try:
    with open('sales_export.json', 'r') as f:
        sales = json.load(f)

    if not sales:
        print('No sales data in input.')
        sys.exit(1)

    product_id = "P001"
    df = pd.DataFrame([s for s in sales if s['product_id'] == product_id])

    if df.empty:
        print(f'No sales data found for product {product_id}.')
        sys.exit(1)

    df['ds'] = pd.to_datetime(df['ds'])
    df['y'] = df['y'].astype(float)

    model = Prophet()
    model.fit(df)

    future = model.make_future_dataframe(periods=7)
    forecast = model.predict(future)

    forecast_json = forecast[['ds', 'yhat']].tail(7).to_dict(orient='records')

    with open(f'forecast_{product_id}.json', 'w') as f:
        json.dump(forecast_json, f)

except Exception as e:
    print('Error:', e)
    sys.exit(1)

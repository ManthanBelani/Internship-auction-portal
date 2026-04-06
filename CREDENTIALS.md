# BidOrbit Test Credentials

## Buyer Accounts

| Email | Password | Notes |
|-------|----------|-------|
| `buyer@bidorbit.com` | `buyer123` | Has bids on 8 items, 2 orders, 2 payment methods, 2 addresses |
| `sarah@bidorbit.com` | `sarah123` | Highest bidder on Rolex, Patek, Gibson, Harry Potter, Jordan |
| `mike@bidorbit.com` | `mike123` | Won Rolex Daytona ($31,500), bids on MacBook, PS5, RTX 4090 |
| `emily@bidorbit.com` | `emily123` | Bids on Samsung TV, Diamond Necklace, Birkin, Jordan |

## Seller Accounts

| Email | Password | Notes |
|-------|----------|-------|
| `seller@bidorbit.com` | `seller123` | 7 listings, $21,897.50 pending payout, 6 messages, 4 reviews |
| `techdeals@bidorbit.com` | `techdeals123` | 4 electronics listings, $997.50 completed payout |
| `luxury@bidorbit.com` | `luxury123` | 3 luxury listings, $29,925 pending payout |
| `vintage@bidorbit.com` | `vintage123` | 4 vintage listings, $20,900 pending payout |

## Admin & Moderator

| Email | Password | Role |
|-------|----------|------|
| `admin@bidorbit.com` | `admin123` | Admin |
| `mod@bidorbit.com` | `mod123` | Moderator |

## Seeded Data Summary

| Table | Records |
|-------|---------|
| Users | 10 |
| Items | 21 (14 active, 4 completed, 2 expired) |
| Bids | 56 |
| Images | 63 |
| Transactions | 4 |
| Reviews | 6 |
| Watchlist | 16 |
| Notifications | 9 |
| Messages | 10 |
| Payment Methods | 6 |
| Shipping Addresses | 5 |
| Orders | 3 |
| Payouts | 4 |

## Re-seed Database

```bash
"D:/xmapp/php/php.exe" seed_mysql.php
```

# sylius switch base currency plugin bundle

> On 1st of January 2023, Croatia switched from local currency Croatian kuna (HRK) to euro (EUR) and a lot of systems had to be updated. Using knowhow gathered on upgrading few of those systems and watching how people struggled, this plugin was hacked. Hopefully somebody may find this useful in future.

![](docs/switch-currency-bundle-demo-q100.gif)

## Requirements

1. Syilius v1.12+
2. BOTH **current base currency** and **new base currency** must be enabled in Sylius Currencies admin
3. Exchange rate set up in Sylius Exchange Rates admin between base and target currencies


## Installation 

```
composer require nivas/sylius-switch-base-currency-bundle
```

## Usage

Do a dry run:

```
bin/console app:switch-base-currency --dry-run
```
Do a real run:

```
bin/console app:switch-base-currency
```


## About

[Sylius](https://sylius.com/) (Open Source Headless eCommerce Platform) stores all product prices in base currency defined upon system setup, which cannot be switched later on. This plugin adds new cli command which will switch base currency and convert product prices into a new base currency.

### What will this command do?

1. Ask for source channel, or select one automatically if only one channel is present
2. Get channel's base currency, and all enabled currencies in the system, then ask for target currency or select automatically if only two different currencies are enabled in the system
3. Look for exchange rates in the system between source and target currency and use appropriate one
4. Change channel's base currency for a target currency
5. Change product prices (price, original price, minimum price from `sylius_channel_pricing`)
6. Change prices of shipping methods
7. Clean up products, totals and adjustments in currently opened user carts in the system

### What this command won't do?

This command will not change any previous order prices or previous order currency. **All previous orders/sales done in a old base currency - will remain in that currency in the system.**

When saving orders, Sylius saves prices in base currency, but it also stores currency code. After base currency is switched, you will see your previous orders in old currency, and new orders in new currency in the admin. 


### Rounding 

Price calculation round on 2 decimals using `PHP_ROUND_HALF_UP` which rounds num away from zero when it is half way there (making 1.555 into 1.56 and -1.555 into -1.56).

```
$newPrice = round($price / $exchangeRate, 2, PHP_ROUND_HALF_UP);
```

### Re-runs

If you re-run command, it will do the reverse exchange rate recalculation. If your exchange rate is not whole number (most probably), **do not expect to have the same prices as before due to rounding**. 

Example:

1. first run: HRK 500.00 -> €66.36
2. second run: €66.36 -> HRK 499.99


### Security & safety

This command wraps all entity changes into into a single transaction, which will in case of error - rollback. However, having backup of your database is highly recommended.

This bundle is provided and intended for use in good faith, the liability for any data loss that may occur as a result of its use rests solely with the user. To limit any potential data loss prior to running the command on a production systems, you should create a database backup.




## Example output

```
$ bin/console app:switch-base-currency
Source channel: sooosuper (default)
Source channel currency: HRK
Destination currency: EUR
Destination currency exchange ratio: 1 EUR = 7.5345 HRK

Warning! This action will make direct changes to your database.

Continue with this action (y/n)? y
Setting new base currency to the channel....
Found 131 product prices to modify.....
price: HRK 1,900.00 -> €252.17 | originalPrice: HRK 1,900.00 -> €252.17 | minimumPrice: HRK 0.00 -> €0.00 | TAM023302 (2)
price: HRK 550.00 -> €73.00 | originalPrice: HRK 550.00 -> €73.00 | minimumPrice: HRK 0.00 -> €0.00 | 6021003 (3)
price: HRK 2,300.00 -> €305.26 | originalPrice: HRK 2,300.00 -> €305.26 | minimumPrice: HRK 0.00 -> €0.00 | RMM000102 (4)
price: HRK 490.00 -> €65.03 | originalPrice: HRK 490.00 -> €65.03 | minimumPrice: HRK 0.00 -> €0.00 | GOIW1XL100 (5)
price: HRK 440.00 -> €58.40 | originalPrice: HRK 440.00 -> €58.40 | minimumPrice: HRK 0.00 -> €0.00 | GOIW3S100 (6)
price: HRK 590.00 -> €78.31 | originalPrice: HRK 590.00 -> €78.31 | minimumPrice: HRK 0.00 -> €0.00 | GOIW2S300 (7)
price: HRK 2,300.00 -> €305.26 | originalPrice: HRK 2,300.00 -> €305.26 | minimumPrice: HRK 0.00 -> €0.00 | BorbaTMM036401 (20)
price: HRK 490.00 -> €65.03 | originalPrice: HRK 490.00 -> €65.03 | minimumPrice: HRK 0.00 -> €0.00 | GOIW2M200 (22)
price: HRK 490.00 -> €65.03 | originalPrice: HRK 490.00 -> €65.03 | minimumPrice: HRK 0.00 -> €0.00 | GOIM2L200 (23)
price: HRK 90.00 -> €11.95 | originalPrice: HRK 90.00 -> €11.95 | minimumPrice: HRK 0.00 -> €0.00 | NNM1L500 (24)
price: HRK 90.00 -> €11.95 | originalPrice: HRK 90.00 -> €11.95 | minimumPrice: HRK 0.00 -> €0.00 | NNM1L600 (25)
price: HRK 490.00 -> €65.03 | originalPrice: HRK 490.00 -> €65.03 | minimumPrice: HRK 0.00 -> €0.00 | GOIM2L200S (26)
price: HRK 390.00 -> €51.76 | originalPrice: HRK 390.00 -> €51.76 | minimumPrice: HRK 0.00 -> €0.00 | GOIW2S200S (27)
price: HRK 390.00 -> €51.76 | originalPrice: HRK 390.00 -> €51.76 | minimumPrice: HRK 0.00 -> €0.00 | GOIW3S200S (28)
price: HRK 590.00 -> €78.31 | originalPrice: HRK 590.00 -> €78.31 | minimumPrice: HRK 0.00 -> €0.00 | BB4M700S (31)
price: HRK 1,900.00 -> €252.17 | originalPrice: HRK 1,900.00 -> €252.17 | minimumPrice: HRK 0.00 -> €0.00 | BorbaTAM023305 (32)
price: HRK 1,900.00 -> €252.17 | originalPrice: HRK 1,900.00 -> €252.17 | minimumPrice: HRK 0.00 -> €0.00 | BorbaRAM300 (33)
price: HRK 2,300.00 -> €305.26 | originalPrice: HRK 2,300.00 -> €305.26 | minimumPrice: HRK 0.00 -> €0.00 | BorbaSMM100 (35)
price: HRK 550.00 -> €73.00 | originalPrice: HRK 550.00 -> €73.00 | minimumPrice: HRK 0.00 -> €0.00 | BBAM1000 (36)
price: HRK 550.00 -> €73.00 | originalPrice: HRK 550.00 -> €73.00 | minimumPrice: HRK 0.00 -> €0.00 | BBAM400 (37)
price: HRK 540.00 -> €71.67 | originalPrice: HRK 540.00 -> €71.67 | minimumPrice: HRK 0.00 -> €0.00 | GOIW3ML300 (38)
price: HRK 550.00 -> €73.00 | originalPrice: HRK 550.00 -> €73.00 | minimumPrice: HRK 0.00 -> €0.00 | D01 (41)
price: HRK 500.00 -> €66.36 | originalPrice: HRK 500.00 -> €66.36 | minimumPrice: HRK 0.00 -> €0.00 | D011 (45)
price: HRK 550.00 -> €73.00 | originalPrice: HRK 550.00 -> €73.00 | minimumPrice: HRK 0.00 -> €0.00 | 6021004 (47)
price: HRK 590.00 -> €78.31 | originalPrice: HRK 590.00 -> €78.31 | minimumPrice: HRK 0.00 -> €0.00 | 6021005 (48)
price: HRK 590.00 -> €78.31 | originalPrice: HRK 590.00 -> €78.31 | minimumPrice: HRK 0.00 -> €0.00 | 6021006 (49)
price: HRK 550.00 -> €73.00 | originalPrice: HRK 550.00 -> €73.00 | minimumPrice: HRK 0.00 -> €0.00 | 6021007 (50)
price: HRK 590.00 -> €78.31 | originalPrice: HRK 590.00 -> €78.31 | minimumPrice: HRK 0.00 -> €0.00 | 6021014 (57)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | RDUCK_UNI_CITY (58)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | RDUCK_W_LONG (60)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | RDUCK_WTAIL (61)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_BACKPACK (62)
price: HRK 1,150.00 -> €152.63 | originalPrice: HRK 1,150.00 -> €152.63 | minimumPrice: HRK 0.00 -> €0.00 | D5_069BG_02 (63)
price: HRK 1,150.00 -> €152.63 | originalPrice: HRK 1,150.00 -> €152.63 | minimumPrice: HRK 0.00 -> €0.00 | D5_069BG (64)
price: HRK 1,150.00 -> €152.63 | originalPrice: HRK 1,150.00 -> €152.63 | minimumPrice: HRK 0.00 -> €0.00 | D5_069DY01 (65)
price: HRK 1,530.00 -> €203.07 | originalPrice: HRK 1,530.00 -> €203.07 | minimumPrice: HRK 0.00 -> €0.00 | D5_080AQ01 (66)
price: HRK 1,150.00 -> €152.63 | originalPrice: HRK 1,150.00 -> €152.63 | minimumPrice: HRK 0.00 -> €0.00 | D5_089AR01 (67)
price: HRK 1,530.00 -> €203.07 | originalPrice: HRK 1,530.00 -> €203.07 | minimumPrice: HRK 0.00 -> €0.00 | D5_087AX01 (68)
price: HRK 1,160.00 -> €153.96 | originalPrice: HRK 1,160.00 -> €153.96 | minimumPrice: HRK 0.00 -> €0.00 | D5_084NM01 (69)
price: HRK 1,770.00 -> €234.92 | originalPrice: HRK 1,770.00 -> €234.92 | minimumPrice: HRK 0.00 -> €0.00 | D5_081AJ01 (71)
price: HRK 1,230.00 -> €163.25 | originalPrice: HRK 1,230.00 -> €163.25 | minimumPrice: HRK 0.00 -> €0.00 | D5_087AT01 (73)
price: HRK 380.00 -> €50.43 | originalPrice: HRK 380.00 -> €50.43 | minimumPrice: HRK 0.00 -> €0.00 | T_00SH13 (74)
price: HRK 930.00 -> €123.43 | originalPrice: HRK 930.00 -> €123.43 | minimumPrice: HRK 0.00 -> €0.00 | T_00SH6T (75)
price: HRK 540.00 -> €71.67 | originalPrice: HRK 540.00 -> €71.67 | minimumPrice: HRK 0.00 -> €0.00 | T_00SI4A (76)
price: HRK 1,150.00 -> €152.63 | originalPrice: HRK 1,150.00 -> €152.63 | minimumPrice: HRK 0.00 -> €0.00 | T_00SILZ (77)
price: HRK 310.00 -> €41.14 | originalPrice: HRK 310.00 -> €41.14 | minimumPrice: HRK 0.00 -> €0.00 | T_00SJAF (78)
price: HRK 1,230.00 -> €163.25 | originalPrice: HRK 1,230.00 -> €163.25 | minimumPrice: HRK 0.00 -> €0.00 | T_00SKMQ (79)
price: HRK 770.00 -> €102.20 | originalPrice: HRK 770.00 -> €102.20 | minimumPrice: HRK 0.00 -> €0.00 | T_00SLPB (80)
price: HRK 930.00 -> €123.43 | originalPrice: HRK 930.00 -> €123.43 | minimumPrice: HRK 0.00 -> €0.00 | T_00SMB2 (81)
price: HRK 760.00 -> €100.87 | originalPrice: HRK 760.00 -> €100.87 | minimumPrice: HRK 0.00 -> €0.00 | T_00SQY2 (82)
price: HRK 540.00 -> €71.67 | originalPrice: HRK 540.00 -> €71.67 | minimumPrice: HRK 0.00 -> €0.00 | T_00SUEW (83)
price: HRK 540.00 -> €71.67 | originalPrice: HRK 540.00 -> €71.67 | minimumPrice: HRK 0.00 -> €0.00 | T_00SUEW8CR (84)
price: HRK 380.00 -> €50.43 | originalPrice: HRK 380.00 -> €50.43 | minimumPrice: HRK 0.00 -> €0.00 | T_0091A100 (85)
price: HRK 380.00 -> €50.43 | originalPrice: HRK 380.00 -> €50.43 | minimumPrice: HRK 0.00 -> €0.00 | T_0091A5HS (86)
price: HRK 380.00 -> €50.43 | originalPrice: HRK 380.00 -> €50.43 | minimumPrice: HRK 0.00 -> €0.00 | KAPA_74CIBRA (87)
price: HRK 540.00 -> €71.67 | originalPrice: HRK 540.00 -> €71.67 | minimumPrice: HRK 0.00 -> €0.00 | KAPA_74CEMBRI (88)
price: HRK 1,100.00 -> €146.00 | originalPrice: HRK 1,100.00 -> €146.00 | minimumPrice: HRK 0.00 -> €0.00 | TOP_90TRUG (89)
price: HRK 850.00 -> €112.81 | originalPrice: HRK 850.00 -> €112.81 | minimumPrice: HRK 0.00 -> €0.00 | 5001 (90)
price: HRK 780.00 -> €103.52 | originalPrice: HRK 780.00 -> €103.52 | minimumPrice: HRK 0.00 -> €0.00 | 5002 (91)
price: HRK 390.00 -> €51.76 | originalPrice: HRK 390.00 -> €51.76 | minimumPrice: HRK 0.00 -> €0.00 | 5003 (92)
price: HRK 890.00 -> €118.12 | originalPrice: HRK 890.00 -> €118.12 | minimumPrice: HRK 0.00 -> €0.00 | M5004 (93)
price: HRK 390.00 -> €51.76 | originalPrice: HRK 390.00 -> €51.76 | minimumPrice: HRK 0.00 -> €0.00 | M5005 (94)
price: HRK 890.00 -> €118.12 | originalPrice: HRK 890.00 -> €118.12 | minimumPrice: HRK 0.00 -> €0.00 | M5006 (95)
price: HRK 390.00 -> €51.76 | originalPrice: HRK 390.00 -> €51.76 | minimumPrice: HRK 0.00 -> €0.00 | M5007 (96)
price: HRK 390.00 -> €51.76 | originalPrice: HRK 390.00 -> €51.76 | minimumPrice: HRK 0.00 -> €0.00 | M5008 (97)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CHE_TUNIKA (99)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2384 (100)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2385 (101)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2387 (102)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2388 (103)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KOD2390 (104)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2391 (105)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2392 (106)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2393 (107)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | BB_T_shirt_KODA2395 (108)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2389 (109)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2396 (110)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2397 (111)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2399 (112)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SIRT_CODE2401 (113)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2403 (114)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2405 (115)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2407 (116)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2409 (117)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2412 (119)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2413 (120)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2414 (121)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2415 (122)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2416 (123)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2417 (124)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2419 (125)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2421 (126)
price: HRK 190.00 -> €25.22 | originalPrice: HRK 0.00 -> €0.00 | minimumPrice: HRK 0.00 -> €0.00 | T-SHIRT_CODE2423 (127)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_BACKPACK_BLUE (130)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_BACKPACK_BEZ (131)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_BACKPACK_CRVENI (132)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_BACKPACK_black (133)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner (134)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_plavi (135)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_zeleni_light (136)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_narancast (137)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_smedi (138)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_smedi_plavi (139)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_zeleni (140)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_svijetloplavi (141)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_plav_zuti (142)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_svijetlo_zuti (143)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_roza (144)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DUCK_zenski_baloner_roza_zuti (145)
price: HRK 3,750.00 -> €497.71 | originalPrice: HRK 3,750.00 -> €497.71 | minimumPrice: HRK 0.00 -> €0.00 | V01W (146)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | KODA_9059 (147)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | KODA_5843 (148)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | KDA_5950 (149)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | KDA5972 (150)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | KDA_5972 (151)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | KODA_6028_TSHIRT (152)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | _KDA6028 (153)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DIESEL_JEANS_KDA6293 (154)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DIESEL_RUKSAK (155)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DIESEL_RUKSAK_KDA6253 (156)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DIESEL_TORBA_KDA6115 (157)
price: HRK 900.00 -> €119.45 | originalPrice: HRK 900.00 -> €119.45 | minimumPrice: HRK 0.00 -> €0.00 | DIESEL_LITTLE_RED_BAG (158)
price: HRK 1,200.00 -> €159.27 | originalPrice: HRK 1,200.00 -> €159.27 | minimumPrice: HRK 0.00 -> €0.00 | DIESEL_JEANS_KDA6483 (159)
price: HRK 250.00 -> €33.18 | originalPrice: HRK 250.00 -> €33.18 | minimumPrice: HRK 0.00 -> €0.00 | VB01uni (160)
price: HRK 220.00 -> €29.20 | originalPrice: HRK 220.00 -> €29.20 | minimumPrice: HRK 0.00 -> €0.00 | VB02uni (161)
price: HRK 250.00 -> €33.18 | originalPrice: HRK 250.00 -> €33.18 | minimumPrice: HRK 0.00 -> €0.00 | VB03uni (162)
price: HRK 200.00 -> €26.54 | originalPrice: HRK 200.00 -> €26.54 | minimumPrice: HRK 0.00 -> €0.00 | V04uni (163)
price: HRK 500.00 -> €66.36 | originalPrice: HRK 500.00 -> €66.36 | minimumPrice: HRK 0.00 -> €0.00 | V005M (164)
price: HRK 500.00 -> €66.36 | originalPrice: HRK 500.00 -> €66.36 | minimumPrice: HRK 0.00 -> €0.00 | V006M (165)
price: HRK 500.00 -> €66.36 | originalPrice: HRK 500.00 -> €66.36 | minimumPrice: HRK 0.00 -> €0.00 | V007M (166)
price: HRK 1,800.00 -> €238.90 | originalPrice: HRK 1,800.00 -> €238.90 | minimumPrice: HRK 0.00 -> €0.00 | V008M (167)
Done modifying product prices.
Found 3 shipping methods to modify.....
Done modifying shipping methods.
Found 24 carts (orders in state 'cart') for cleanup.
Reseting cart 1 totals, adjustments, items (1)
Reseting cart 3 totals, adjustments, items (0)
Reseting cart 4 totals, adjustments, items (1)
Reseting cart 5 totals, adjustments, items (1)
Reseting cart 6 totals, adjustments, items (1)
Reseting cart 8 totals, adjustments, items (1)
Reseting cart 9 totals, adjustments, items (1)
Reseting cart 10 totals, adjustments, items (1)
Reseting cart 11 totals, adjustments, items (1)
Reseting cart 15 totals, adjustments, items (1)
Reseting cart 17 totals, adjustments, items (1)
Reseting cart 19 totals, adjustments, items (1)
Reseting cart 20 totals, adjustments, items (1)
Reseting cart 21 totals, adjustments, items (1)
Reseting cart 22 totals, adjustments, items (1)
Reseting cart 23 totals, adjustments, items (1)
Reseting cart 24 totals, adjustments, items (0)
Reseting cart 25 totals, adjustments, items (0)
Reseting cart 26 totals, adjustments, items (1)
Reseting cart 27 totals, adjustments, items (1)
Reseting cart 28 totals, adjustments, items (1)
Reseting cart 29 totals, adjustments, items (1)
Reseting cart 30 totals, adjustments, items (1)
Reseting cart 31 totals, adjustments, items (1)
Done cleaning carts.
Currency switch done.
```


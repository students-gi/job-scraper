# LV Job Scraper

EN: This is a simple job scraper written in PHP that fetches IT job offers from various vendors. The application utilizes "illegal" API calls to websites for clean JSON data where possible, and PHP and DOMXPath to parse the HTML content of the job offer websites and extract relevant job details where not.

LV: Vienkārša PHP lietotne kas apkopo IT darba piedāvājumus no dažādām vietnēm. Datu ieguvei izmantoti "nelegāli" API pieprasījumi tīru JSON datu ieguvei kur tas iespējams un PHP DOMXPath datu nolasīšanai no HTML kur tādas iespējas nav.

Rest is gonna be in Latvian, so you better get a translator handy

## Funcionalitāte

1. Nolasa info no visām mājaslapām kam izveidots reģistrēts `scraper` modulis
2. ~~Filtrē ārā *blacklisted* datus~~
3. ~~Sakārto vakances loģiskā secībā~~
4. Saglabā atrastos darba piedāvājumus vietējā datu bāzē vai datu failos.
5. Attēlo nolasītos datus atvērtajā pārlūkā

#### TODO:
- Sakārtot vakanču filtrāciju lai tā normāli strādā
- Salabot kārtošanu, lai pēc tās dati netiktu zaudēti
- Padarīt vakanču saglabāšanu un atjaunināšanu automātisku

## Instalācija

Pirms centies to darīt, pārliecinies ka sekojošās lietas ir ieinstalētas:
- PHP >= 8.0,
- PHP DOMXPath
- cURL
```bash
git clone https://github.com/students-gi/job-scraper.git
cd job-scraper
php -S localhost:8069
```
Portu vari mainīt uz ko vēlies; es vienkārši uzliku uz to ciparu jo man parasti viss kas cits jau atrodas uz 8000 un 8080.

## Palīdzēšana

Ja gribi palīdzēt pilnveidot šo manu 3 dienu projektu vai ziņot par kādām problēmām, lūdzams izveido jaunu `Pull Request` vai `Issue`. Pirmie tiks (loģiski) izskatīti krietni laipnāk nekā pārējie.
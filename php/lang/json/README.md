# Engelse vertalingen

De site is in het Nederlands geschreven. Vaste teksten in de code staan in `__('Nederlandse tekst')`; in het Engels
zoekt Laravel de vertaling op in de bestanden hieronder (sleutel = de Nederlandse tekst, waarde = Engels). Ontbreekt
een vertaling, dan blijft de Nederlandse tekst staan.

| Map | Inhoud |
| --- | --- |
| `site/en.json` | Openbare website: kop, footer, contactformulier, foutpagina's, gestructureerde gegevens. |
| `account/en.json` | Inloggen, registreren, tweestapsverificatie, Mijn omgeving. |
| `app/en.json` | Gedeelde onderdelen: intake, agenda, voortgang, schema-weergave, datums. |

De teksten die Steyn in het beheer aanpast (Website-teksten) staan niet hier: die hebben in het beheer een eigen
Engelse versie (zie `app/Content/English.php`).

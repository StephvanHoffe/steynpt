# Downloads

- `steynpt-php-website.zip`: het installatiepakket van de website (de nieuwste versie). Dit bestand wordt
  bijgewerkt door `bash deploy/maak-pakket.sh` in `php/`. De downloadknop in de afvinklijst
  ([docs/online-zetten/index.html](../docs/online-zetten/index.html)) wijst hiernaartoe.

Er staan geen wachtwoorden of sleutels in het pakket: die vul je pas op de hosting in (stap 8 van de afvinklijst).

- `controle.sh`: laat op de hosting zien hoe ver het online zetten is (per deel van de afvinklijst) en waar je
  verder moet. Verandert niets. Na inloggen via SSH:

  ```bash
  curl -fsSL https://github.com/StephvanHoffe/steynpt/raw/claude/dreamy-brown-cauqyb/downloads/controle.sh | bash
  ```

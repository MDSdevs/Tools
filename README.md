# USO

./add-new-release.sh ROM_ZIP.zip GIT_BASE_DIR NEW_ROM_RELEASE PREVIOUS_ROM_RELEASE

## EJEMPLO

./add-new-release.sh VIBEUI_V3.1_1614_LEMONADE.zip "/home/usuario/www/git/MovilesDualSimLTT" "V3.1_1614_5.294.1_ST" "V3.5_1610_3.40.1_DEV"

## COMENTARIO

El directorio /home/usuario/www/git/MovilesDualSimLTT debe contener los dos repositorios de traducciones, el Base (MovilesDualSimLTT-Lenovo-K3-Note-VibeUI-Translations-Base) y el del idioma correspondiente (MovilesDualSimLTT-Lenovo-K3-Note-VibeUI-Translations-es).

# PROCESO

## BASH

1.  Extraer ROM
2.  Decompilar APK
3.  Generamos un branch con la versión de la ROM a traducir
4.  Copiar todas los directorios de strings de todos los idiomas (res/values*) para el repositorio GIT-BASE
5.  Por cada idioma copiamos los directorios res/values y lo renombramos como res/values-IDIOMA
6.  Esta traducción base la añadimos al repositorio de GIT-IDIOMA

## PHP

7.  Nos situamos en el último branch, que contiene las traducciones originales de la ROM
7.  Se buscan todos los ficheros XML en el directorio de GIT-IDIOMA (archivos originales que vienen de GIT-BASE)
8.  Se cargan los ficheros XML como arrays
9.  Se buscan todos los ficheros XML relacionados con el idioma actual en GIT-BASE (res/values-IDIOMA, res/values-IDIOMA-rIDIOMA)
10. Se sustituyen las cadenas de texto que existan en el idioma actual
12. Nos situamos en el branch para anterior, donde estarán las últimas traducciones aplicadas a esta ROM
13. Se buscan todos los ficheros XML relacionados con el idioma actual en GIT-IDIOMA (res/values-IDIOMA)
14. Se sustituyen las cadenas de texto que existan en el idioma actual
15. Se reescriben los XML del directorio GIT-IDIOMA
16. Esta traducción se añade al repositorio GIT-IDIOMA

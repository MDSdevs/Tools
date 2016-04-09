# BASH

1.  Extraer ROM
2.  Decompilar APK
3.  Generamos un branch con la versión de la ROM a traducir
4.  Copiar todas los directorios de strings de todos los idiomas (res/values*) para el repositorio GIT-BASE
5.  Por cada idioma copiamos los directorios res/values y lo renombramos como res/values-IDIOMA
6.  Esta traducción base la añadimos al repositorio de GIT-IDIOMA

# PHP

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

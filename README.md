<div align="center">
    <br/>
    <img src=".github/readme/etsii-claro.png#gh-light-mode-only" height=100 alt="ETSII logo (claro)"/>
    <img src=".github/readme/etsii-oscuro.png#gh-dark-mode-only" height=100 alt="ETSII logo (oscuro)"/>
    <br/>
</div>

# TFG - Proyecto (plataforma)

Este repositorio contiene una página de WordPress correspondiente a la plataforma web del proyecto de TFG.

> **Note**  
> El proyecto de TFG consta de 2 partes: esta plataforma web, y los [laboratorios de pentesting](https://github.com/15Galan/tfg_laboratorios); será necesario usar ambos repositorios para poder usar la plataforma correctamente.

## Uso del repositorio

Dada la naturaleza de los ficheros de este repositorio, bastaría con clonarlo en la carpeta `/var/www/html` de un servidor Apache, y cargar la base de datos MySQL correspondiente. Sin embargo, no se cuenta con una copia de seguridad de la base de datos, por lo que este proceso simple no sería posible.

No obstante, sí existe una copia de seguridad del sistema completo gracias al plugin WPVivid de WordPress, por lo que se puede replicar la plataforma de la siguiente manera:

1. Crear una instancia de WordPress en un servidor Apache.
2. Instalar el plugin [WPvivid](https://es.wordpress.org/plugins/wpvivid-backuprestore) en la instancia de WordPress.
3. Importar la copia de seguridad `html-wpvivid.zip` del repositorio.

> **Note**  
> Si se desea usar este repositorio (o un *fork*), podría realizarse un paso 4 como el siguiente:
>
> 4. Eliminar `/var/www/html` y sustituirla por un clon del repositorio.
>
> Esto se hace en este punto porque la base de datos ya se creó en el paso 3.

## Información del proyecto

Se emplea un tema de WordPress personalizado llamado *divi-child* -basado en el tema *Divi* de Elegant Themes-, donde se han implementado todas las funcionalidades de la plataforma.

El fichero `functions.php` contiene el código PHP que implementa las funcionalidades necesarias para gestionar los laboratorios de pentesting mencionados al inicio: crear y detener laboratorios, asociar los laboratorios a los usuarios, etc.

> **Note**  
> El sistema original cuenta con una tarea cron para destruir automáticamente los laboratorios que lleguen a 1 hora de ejecución. Se puede comprobar y replicar dicha tarea usando el fichero `/scripts/stop-1h-containers.sh` de este repositorio, ya que aparece comentada al final.

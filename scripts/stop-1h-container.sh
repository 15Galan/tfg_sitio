#!/bin/bash


# Variables "globales"
CONTENEDORES=$(docker ps -q)    # Lista de contenedores activos (IDs)
VIDA=3600                       # Tiempo de vida de un contenedor (en segundos)


# Mostrar información de la ejecución
echo -e "\e[1mEjecución actual: $(date +%H:%M:%S)\e[0m\n"
if [ -z "$CONTENEDORES" ]; then
    echo -e "No hay contenedores activos.\n"
else
    echo -e "Hay $(echo $CONTENEDORES | wc -w) contenedores activos.\n"
fi


# Recorrer los contenedores activos
for CONTENEDOR in $CONTENEDORES; do
    # Tiempo de creación del contenedor (ISO 8601)
    CREACION=$(docker inspect --format '{{ .State.StartedAt }}' $CONTENEDOR)

    INICIO=$(date --date="$CREACION" +%s)       # Tiempo de ejecución (en segundos)
    AHORA=$(date +%s)                           # Tiempo actual (en segundos)
    DIFF=$(( $AHORA - $INICIO ))                # Tiempo transcurrido (en segundos)

    # Crear información del estado de un contenedor
    MSG="$CONTENEDOR:\t$DIFF / $VIDA segundos."

    # Comprobar que el contenedor superó el tiempo de vida
    if [ $DIFF -gt $VIDA ]; then
        echo -e "$MSG \e[31mTiempo de vida alcanzado.\e[0m"
        
        docker stop $CONTENEDOR > /dev/null

    else
        if [ $DIFF -gt $(( $VIDA - 60 )) ]; then
            echo -e "$MSG \e[33mDestrucción inminente.\e[0m"
        else
            echo -e "$MSG \e[32mDisponible.\e[0m"
        fi
    fi
done

echo ""


# Tarea de cron:
# * * * * * /bin/bash /var/www/html/scripts/stop-1h-container.sh > /var/www/html/scripts/stop-1h-container.log 2>&1

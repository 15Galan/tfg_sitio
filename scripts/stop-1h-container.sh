#!/bin/bash


# Contenedores activos (IDs)
CONTENEDORES=$(docker ps -q)

# Tiempo de vida de un contenedor (en segundos)
VIDA=3600


# Mostrar la hora de ejecución del script (negrita)
echo "\e[1mEjecución actual: $(date +%H:%M:%S)\e[0m"
echo ""

# Mostrar la cantidad de contenedores activos
echo "Hay $(echo $CONTENEDORES | wc -w) contenedores activos."
echo ""


# Recorrer los contenedores activos
for CONTENEDOR in $CONTENEDORES; do
    # Tiempo de ejecución del contenedor (ISO 8601)
    TIEMPO=$(docker inspect --format '{{ .State.StartedAt }}' $CONTENEDOR)

    # Tiempo actual (en segundos)
    AHORA=$(date +%s)
    
    # Tiempo de ejecución (en segundos)
    TIEMPO=$(date --date="$TIEMPO" +%s)
    
    # Diferencia de tiempo (en segundos)
    DIFF=$(( $AHORA - $TIEMPO ))

    # Crear información del estado de un contenedor
    MSG="$CONTENEDOR:\t$DIFF / $VIDA segundos."


    # Comprobar que el contenedor superó el tiempo de vida
    if [ $DIFF -gt $VIDA ]; then
        echo -e "$MSG \e[31mTiempo alcanzado.\e[0m"
        
        docker stop $CONTENEDOR > /dev/null
    else
        if [ $DIFF -gt $(( $VIDA - 60 )) ]; then
            echo -e "$MSG \e[33mTiempo escaso.\e[0m"
        else
            echo -e "$MSG \e[32mTiempo disponible.\e[0m"
        fi
    fi
done

echo ""


# Tarea de cron:
# * * * * * /bin/bash /var/www/html/scripts/stop-1h-container.sh > /var/www/html/scripts/stop-1h-container.log 2>&1

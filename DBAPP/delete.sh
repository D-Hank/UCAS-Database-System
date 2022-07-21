echo "Warning: you are deleting the database!"
read -p "Are you sure to do so? [y/n]  " answer

tables=("station" "scheduler" "seat" "ticket" "has_ticket" "ordering" "passenger")

case $answer in
    [yY][eE][sS]|[yY])
        for ((i=0;i<${#tables[*]};i++))
        do
            psql -U ubuntu -d test -p 5434 -c "drop table ${tables[i]} cascade;"
        done
        ;;

    *)
        exit 0
        ;;
esac

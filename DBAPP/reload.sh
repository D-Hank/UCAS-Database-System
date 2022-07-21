echo "Reloading data into database..."

tables=("station" "scheduler" "seat" "ticket")

for ((i=0;i<${#tables[*]};i++))
do
    psql -U ubuntu -d test -p 5434 -c "\copy ${tables[i]} from '/home/ubuntu/Desktop/database-system/table/${tables[i]}.tbl' with csv;"
done

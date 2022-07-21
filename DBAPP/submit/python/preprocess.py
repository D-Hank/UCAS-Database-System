import csv
import re
import os
import argparse
import datetime
import sys

ROOT_DIR = "./train-2016-10/"
CSV_PATH = ROOT_DIR + "0/1095.csv"
ALL_STATION = ROOT_DIR + "all-stations.txt"

STATION_PATTERN = r"[\u4e00-\u9fff]+"
TIME_PATTERN = r"[(\d \d : \d \d)]"
INTERVAL_PATTERN = r"[(\d+ \u5206)]"
DURATION_PATTERN = r"[\d+]"
MILEAGE_PATTERN = r"[\d+]"
SEAT_PRICE = r"[\d+ \. \d+ / -]"
SEAT_DEFAULT = r"[- / -]"
EMPTY_PATTERN = r"^$"
DEFAULT_PATTERN = r"[-$]"

SAVE_DIR = "./table/"

station = {}
st_array = []
city = {}
scheduler_file = None
seat_file = None
start_date = '2022-05-09'
end_date = '2022-05-16'

def get_station():
    city_id = 1
    with open(ALL_STATION, 'r', encoding = 'utf-8') as txt_file:
        txt_reader = csv.reader(txt_file)

        save_file = open(SAVE_DIR + "station.tbl", mode = 'w', encoding = 'utf-8')

        for line in txt_reader:
            st_id = line[0].strip()
            st = line[1].strip()
            ct = line[2].strip()

            pair = [st_id, st, str(city_id)]
            st_array.append(pair)
            save_file.write(st_id + "," + st + "," + ct + "\n")
            station[st] = st_id

            # A new city
            if ct not in city:
                city[ct] = str(city_id)
                city_id += 1

        save_file.close()

def get_stamp(time):
    return int(time[0:2]) * 60 + int(time[3:5]) 

class make_data():
    def preprocess(self, file_path = CSV_PATH):
        title = []
        self.num_elements = 0
        all_line = []
        with open(file_path, 'r', encoding = 'utf-8') as csv_file:
            csv_reader = csv.reader(csv_file)

            # Retrieve the title
            title = next(csv_reader)
            self.num_elements = len(title)
            for line in csv_reader:
                # Drop the spaces
                line_element = []
                for element in line:
                    line_element.append(element.strip())

                all_line.append(line_element)

        # Check each column and convert
        order = 1
        def illegal_pos(info):
            return "Illegal " + info + " for line %d, file %s" % (order, file_path)

        def check_data():
            num_lines = len(all_line)
            for i in range(num_lines):
                # First column
                assert int(all_line[i][0]) == order, illegal_pos("line number")

                # Second column
                assert re.match(STATION_PATTERN, all_line[i][1]), illegal_pos("station name")

                # Third column
                assert re.match(TIME_PATTERN, all_line[i][2]) != None or re.match(DEFAULT_PATTERN, all_line[i][2]) != None, illegal_pos("arrive time")

                # Fourth column
                assert re.match(TIME_PATTERN, all_line[i][3]) != None or re.match(DEFAULT_PATTERN, all_line[i][3]) != None, illegal_pos("depart time")

                # 5th column
                assert re.match(INTERVAL_PATTERN, all_line[i][4]) != None or re.match(EMPTY_PATTERN, all_line[i][4]) != None or re.match(DEFAULT_PATTERN, all_line[i][4]) != None, illegal_pos("stay time")

                # 6th
                assert (0 if all_line[i][5] == "-" else int(all_line[i][5])) >= 0, illegal_pos("duration")

                # 7th
                assert (0 if all_line[i][5] == "-" else int(all_line[i][6])) >= 0, illegal_pos("mileage")

                # price
                total = 0.0
                for j in range(7, 10):
                    all_price = all_line[i][j].split("/")
                    for price in all_price:
                        p = (float(price) if price != '-' else 0.0)
                        total += p
                        if p < 0.0:
                            print(illegal_pos("price"))

                if total < 0.0 or (total == 0.0 and i == (num_lines - 1)):
                    #print(illegal_pos("price"))
                    #return
                    pass

                order += 1

        all_line[0][5] = "0"
        day_span = 0
        self.num_lines = len(all_line)
        self.rank = []
        self.station_id = []
        self.arrive_time = []
        self.depart_time = []
        self.stay_time = []
        self.duration = []
        self.mileage = []
        self.price = []
        self.is_end = []

        for i in range(0, self.num_lines):
            id = all_line[i][0]
            station_name = all_line[i][1]
            arrive_time = all_line[i][2]
            depart_time = all_line[i][3]
            stay_time = all_line[i][4]
            duration = all_line[i][5]
            mileage = all_line[i][6]
            price_i = [all_line[i][7], all_line[i][8], all_line[i][9]]

            station_id = station[station_name]
            is_end = 'True' if i == (self.num_lines - 1) else 'False'
            if(arrive_time == '-' or arrive_time == ''):
                arrive_time = depart_time
                all_line[i][2] = depart_time

            if(depart_time == '-' or depart_time == ''):
                depart_time = arrive_time
                all_line[i][3] = arrive_time

            # Between 2 days: arrive ~ depart or arrive ~ arrive
            if i != 0:
                if(get_stamp(arrive_time) < get_stamp(all_line[i-1][2])):
                    day_span += 1
                    duration = str(int(all_line[i-1][5]) + get_stamp(arrive_time) + get_stamp("24:00") - get_stamp(all_line[i-1][2]))
                else:
                    duration = str(int(all_line[i-1][5]) + get_stamp(arrive_time) - get_stamp(all_line[i-1][2]))

                all_line[i][5] = duration

            arrive_time = arrive_time + ":00"
            depart_time = depart_time + ":00"

            price_temp = []
            for j in range(3):
                # seat, hard bed, soft bed
                length = 3 if j == 1 else 2
                if price_i[j] == "-":
                    for k in range(length):
                        price_temp.append("0.0")
                else:
                    price_each = price_i[j].split("/")
                    for k in range(length):
                        price_temp.append("0.0" if price_each[k] == "-" else price_each[k])

            self.rank.append(id)
            self.station_id.append(station_id)
            self.arrive_time.append(arrive_time)
            self.depart_time.append(depart_time)
            self.stay_time.append(stay_time)
            self.duration.append(duration)
            self.mileage.append(mileage)
            self.price.append(price_temp)
            self.is_end.append(is_end)


    def __init__(self, file_name, file_path, start_date, end_date):
        self.train_num = file_name[ :-4]
        self.preprocess(file_path)
        self.seat_type = ['H', 'S', 'HU', 'HM', 'HL', 'SU', 'SL']
        self.which_seat = ['A', 'B', 'C', 'D', 'E']
        self.start_date = start_date
        self.end_date = end_date

    def make_scheduler(self):
        for i in range(self.num_lines):
            scheduler_file.write(
                self.station_id[i] + "," +
                self.train_num + "," +
                self.arrive_time[i] + "," +
                self.depart_time[i] + "," +
                self.duration[i] + " min," +
                self.rank[i] + "," +
                self.is_end[i] + "\n")

    def make_seat(self):
        for i in range(self.num_lines):
            for j in range(len(self.seat_type)):
                seat_file.write(
                    self.station_id[i] + "," +
                    self.train_num + "," +
                    self.seat_type[j] + "," +
                    self.price[i][j] + "\n"
                )

    def make_ticket(self):
        # start ~ end - 1
        global start_id
        date = self.start_date
        while(date != self.end_date):
            for which in self.which_seat:
                for type in self.seat_type:
                    ticket_file.write(
                        str(start_id) + "," +
                        self.train_num + "," +
                        date + "," +
                        which + "," +
                        "Available" + "," +
                        self.station_id[0] + "," +
                        self.station_id[self.num_lines - 1] + "," +
                        type + "\n"
                    )
                    start_id += 1

            date = datetime.date.strftime(datetime.datetime.strptime(date, "%Y-%m-%d") + datetime.timedelta(days = 1.0), "%Y-%m-%d")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description = "DB2 argument parser")
    parser.add_argument("--ticket", type = bool, default = False)
    parser.add_argument("--id", type = int, default = 1)
    parser.add_argument("--start", type = str, default = start_date)
    parser.add_argument("--end", type = str, default = end_date)
    args = parser.parse_args()

    global start_id

    ticket_only = args.ticket
    start_id = args.id
    start_date = args.start
    end_date = args.end

    get_station()

    if ticket_only == False:
        scheduler_file = open(SAVE_DIR + "scheduler.tbl", mode = 'w', encoding = 'utf-8')
        seat_file = open(SAVE_DIR + "seat.tbl", mode = 'w', encoding = 'utf-8')
    else:
        ticket_file = open(SAVE_DIR + "ticket.tbl", mode = 'w', encoding = 'utf-8')

    for sub_dir in os.listdir(ROOT_DIR):
        dir_name = os.path.join(ROOT_DIR, sub_dir)
        if os.path.isdir(dir_name):
            for file in os.listdir(dir_name):
                file_path = os.path.join(dir_name, file)
                data_in_file = make_data(file, file_path, start_date, end_date)

                if ticket_only == False:
                    data_in_file.make_scheduler()
                    data_in_file.make_seat()
                else:
                    data_in_file.make_ticket()
                #exit(0)

    # Output
    print(start_id)

    if ticket_only == False:
        scheduler_file.close()
        seat_file.close()
    else:
        ticket_file.close()

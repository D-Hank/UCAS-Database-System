
Station: BCNF, 只有一个键
Scheduler: (TrainNum, Ranking) 是一个候选键，Tr不能确定其他的属性，R也是
            (TrainNum, StationID) 是主键，StationID不能确定其他属性
            所以是2NF
            StayTime和IsStart因为冗余，删除了
            3NF：不完全包含上述两个键的X都不能确定剩余的属性，所以是3NF
            BCNF: KEY<->KEY只可能存在于上述两个键中，X=Ranking, A=StationID或反过来都不能确定；或者，X不完全包含上述两个键，则X也不能确定出KEY'中的TrainNum, Ranking, 或StationID
            于是BCNF

Seat: (TrainNum, StationID, SeatType)是主键，也是唯一候选键
    2NF：缺少其中一个都不能确定剩余属性，没有部分依赖
    3NF：同上
    BCNF：同上

Ticket：ID为主键，其余的属性可以构成一个候选键
    2NF和3NF：没有A
    BCNF：候选键中的属性彼此独立，无法确定出该候选键中的某一属性A

HasTicket: 
    2NF和3NF：没有A
    BCNF：联合主键中的TicketID无法确定OrderID，因为同一张票可能对应多个订单（退了又买）

Order: ID是主键，BuyTime是候选键（如果不考虑并发）
    BCNF：因为一定有KEY=X

Passenger: Phone是主键，UserName是候选键
    BCNF: 因为一定有KEY=X

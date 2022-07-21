/**
 * @file    executor.cc
 * @author  liugang(liugang@ict.ac.cn)
 * @version 0.1
 *
 * @section DESCRIPTION
 *  
 * definition of executor
 * 
 */
#include "executor.h"

int Executor::findCol(char* table_name, char* column_name)
{
    Table* table = (Table*)g_catalog.getObjByName(table_name);
    int64_t columnid = ((Column*)g_catalog.getObjByName(column_name))->getOid();
    return table->getColumnRank(columnid);
}

int64_t Executor::getRank(std::vector < int64_t > &vec, int64_t id) {
    for (unsigned int ii = 0; ii < vec.size(); ii++)
        if (vec[ii] == id)
            return ii;
    return -1;
}

/* the operator tree looks like:
 * (table) -> scan -> filter -> join -> groupbyAggregate -> having(filter) -> orderby -> project
 */
Operator* Executor::planner(SelectQuery *query)
{
    // the top operators of selected tables
    void* Op_list[4];
    // if a table has operator which is not a scan
    // the indicator of IndexNestedLoop join
    int checked[4] = {0, 0, 0, 0};
    // the schema of the middle results
    std::vector<int64_t> schema[4];
    
    // normal scan on selected tables
    for(int i=0; i<query->from_number; i++)
    {
        // scan operator
        Scan* scan = new Scan();
        Table* table = (Table*)g_catalog.getObjByName(query->from_table[i].name);
        scan->setTable(table);
        // get schema of scan
        std::vector <int64_t> col_id = table->getColumns();
        Op_list[i] = scan;
        schema[i] = col_id;
    }

    // Filter opreator
    for(int i=0; i<query->where.condition_num; i++)
    {
        if(query->where.condition[i].compare != LINK)
        {   
            for(int j=0; j<query->from_number; j++)
            {
                int col_rank = -1;
                col_rank = findCol(query->from_table[j].name, query->where.condition[i].column.name);
                if(col_rank >= 0)
                {
                    // find column[i] in table[j]
                    // checked
                    if(checked[j] == 0)
                        checked[j] = 1;
                    // new filter
                    Filter* filter = new Filter();
                    filter->setChild((Operator*)Op_list[j]);
                    filter->setColumn(schema[j], col_rank, query->where.condition[i].compare, query->where.condition[i].value);
                    Op_list[j] = filter;
                }
            }
        }
    }

    // join operator
    for(int i=0; i<query->where.condition_num; i++)
    {
        if(query->where.condition[i].compare == LINK)
        {   
            int col_rank1;
            int col_rank2;
            int j, k;
            // which table has the join key
            for(j=0; j < query->from_number; j++)
            {
                int64_t col_id1 = ((Column*)g_catalog.getObjByName(query->where.condition[i].column.name))->getOid();
                col_rank1 = getRank(schema[j], col_id1);
                if(col_rank1 >= 0) break;
            }
            if(j==query->from_number)
            {
                printf("no such join key!");
                exit(1);
            }
            for(k=0; k < query->from_number; k++)
            {
                int64_t col_id2 = ((Column*)g_catalog.getObjByName(query->where.condition[i].value))->getOid();
                col_rank2 = getRank(schema[k], col_id2);
                if(col_rank2 >= 0) break;
            }
            if(k==query->from_number)
            {
                printf("no such join key!");
                exit(1);
            }

            // whether table1/2 has the hash index
            bool index_checked1 = false;
            bool index_checked2 = false;
            Table* index_table = NULL;
            Index* index_need = NULL;
            int another_oplistid = 0;

            int64_t col_id1 = ((Column*)g_catalog.getObjByName(query->where.condition[i].column.name))->getOid();
            Table* table1 = (Table*)g_catalog.getObjByName(query->from_table[j].name);
            int indexnum1 = table1->getIndexs().size();
            for (int ii= 0; ii< indexnum1; ii++)
            {
                Index* index1 = (Index*) g_catalog.getObjById (table1->getIndexs()[ii]);
                if(index1->getIKey().contain(col_id1) && (checked[j] == 0))
                {   // table1 should use indexscan
                    index_checked1 = true;
                    index_table = table1;
                    index_need = index1;
                    another_oplistid = k;
                    break;
                }
            }
            if(!index_checked1)
            {
                int64_t col_id2 = ((Column*)g_catalog.getObjByName(query->where.condition[i].value))->getOid();
                Table* table2 = (Table*)g_catalog.getObjByName(query->from_table[k].name);
                int indexnum2 = table2->getIndexs().size();
                for (int ii= 0; ii< indexnum2; ii++)
                {
                    Index* index2 = (Index*) g_catalog.getObjById (table2->getIndexs()[ii]);
                    if(index2->getIKey().contain(col_id2) && (checked[k] == 0))
                    {   //table2 should use indexscan
                        index_checked2 = true;
                        index_table = table2;
                        index_need = index2;
                        another_oplistid = j;
                        int temp = col_rank1;
                        col_rank1 = col_rank2;
                        col_rank2 = temp;
                        break;
                    }
                }
            }
            
            if(index_checked1 || index_checked2)
            {
                // IndexNestedLoop join
                IndexScan* scan = new IndexScan();
                scan->setTabIdx(index_table, index_need);
                checked[j] = 1;
                checked[k] = 1;
                // get the output schema
                std::vector <int64_t> input_colid1 = index_table->getColumns();
                std::vector <int64_t> input_colid2 = schema[another_oplistid];
                std::vector <int64_t> output_colid;
                output_colid.insert(output_colid.end(), input_colid1.begin(), input_colid1.end());
                output_colid.insert(output_colid.end(), input_colid2.begin(), input_colid2.end());
                
                IndexJoin* join = new IndexJoin();

                join->setJoinCol(input_colid1, input_colid2, col_rank1, col_rank2);
                join->setLeftOp(scan);
                join->setRightOp((Operator*)Op_list[another_oplistid]);

                for(int ii = 0; ii<query->from_number; ii++)
                {
                    if(Op_list[ii]==Op_list[j] || Op_list[ii]==Op_list[k])
                    {
                        Op_list[ii] = join;
                        schema[ii] = output_colid;
                    }
                }
            }
            else
            {
                // hash join
                std::vector <int64_t> input_colid1 = schema[j];
                std::vector <int64_t> input_colid2 = schema[k];
                std::vector <int64_t> output_colid;
                output_colid.insert(output_colid.end(), input_colid1.begin(), input_colid1.end());
                output_colid.insert(output_colid.end(), input_colid2.begin(), input_colid2.end());
                
                HashJoin* join = new HashJoin();
                
                join->setJoinCol(input_colid1, input_colid2, col_rank1, col_rank2);

                join->setLeftOp((Operator*)Op_list[j]);
                join->setRightOp((Operator*)Op_list[k]);

                for(int ii = 0; ii<query->from_number; ii++)
                {
                    if(Op_list[ii]==Op_list[j] || Op_list[ii]==Op_list[k])
                    {
                        Op_list[ii] = join;
                        schema[ii] = output_colid;
                    }
                }
                checked[j] = 1;
                checked[k] = 1;
            }
              
        }
    }
    // groupbyaggr operator
    if(query->groupby_number!=0)
    {
        std::vector <int64_t> groupby_output;
        std::vector <int64_t> groupby_rank;
        std::vector <AggreCondition> conditions;
        // foreach groupby conditions, get the rank and output schema
        for(int i=0; i<query->groupby_number; i++)
        {
            int64_t col_id = ((Column*)g_catalog.getObjByName(query->groupby[i].name))->getOid();
            int rank = getRank(schema[0], col_id);
            if(rank < 0) { exit(1); }
            groupby_output.push_back(col_id);
            groupby_rank.push_back(rank);
        }
        // foreach aggregation method
        for(int i=0; i<query->select_number; i++)
        {
            if(query->select_column[i].aggregate_method != NONE_AM)
            {
                // need new column id
                int64_t col_id;
                ColumnType type;
                // should change the data type for COUNT
                if(query->select_column[i].aggregate_method == COUNT)
                    type = INT64;
                else
                    type = ((Column*)g_catalog.getObjByName(query->select_column[i].name))->getCType();
                char newname[128];
                sprintf(newname, "%d", query->select_column[i].aggregate_method);
                strcat(newname, query->select_column[i].name);
                if(!(g_catalog.getObjByName(newname)))
                {   
                    //not existed, then create one
                    g_catalog.createColumn((const char *)newname, type, 0, col_id);
                    ((Column*)g_catalog.getObjById(col_id))->init();
                }
                else
                {
                    //existed, then use it, for it doesn't offer the delete function
                    col_id=((Column*)g_catalog.getObjByName(newname))->getOid();
                }
                groupby_output.push_back(col_id);
                // the framework not support delete the column
                // for input, use the previous column id
                col_id = ((Column*)g_catalog.getObjByName(query->select_column[i].name))->getOid();
                AggreCondition condition;
                condition.column_rank = getRank(schema[0], col_id);
                if(condition.column_rank < 0) { exit(1); }
                condition.method = query->select_column[i].aggregate_method;
                conditions.push_back(condition);
            }
        }
        GroupbyAggre* groupby = new GroupbyAggre();
        groupby->setChild((Operator*)Op_list[0]);
        groupby->set(schema[0], groupby_rank, conditions, groupby_output);
        schema[0] = groupby_output;
        Op_list[0] = groupby;
    }

    // having condition, filter operator
    for(int i=0; i<query->having.condition_num; i++)
    {
        if(query->having.condition[i].compare != LINK)
        {   
            int64_t col_id = ((Column*)g_catalog.getObjByName(query->having.condition[i].column.name))->getOid();
            int col_rank = getRank(schema[0], col_id);
            if(col_rank < 0) { exit(1); }
            
            Filter* filter = new Filter();
            filter->setChild((Operator*)Op_list[0]);
            filter->setColumn(schema[0], col_rank, query->having.condition[i].compare, query->having.condition[i].value);
            Op_list[0] = filter;
        }
    }

    // orderby operator
    if(query->orderby_number!=0)
    {
        std::vector<int> col_rank;
        for(int i=0;i<query->orderby_number;i++)
        {
            int64_t colid = ((Column*)g_catalog.getObjByName(query->orderby[i].name))->getOid();
            int rank = getRank(schema[0], colid);
            col_rank.push_back(rank);
        }
        Orderby* order = new Orderby();
        order->set(schema[0], col_rank);
        order->setChild((Operator*)Op_list[0]);
        Op_list[0] = order;
    }

    // project operator
    std::vector <int64_t> input_colid = schema[0];
    std::vector <int64_t> output_colid;
    for(int i=0; i<query->select_number; i++)
    {
        int64_t colid;
        if(query->select_column[i].aggregate_method != NONE_AM)
        {
            // for aggregation, get the new column inserted
            char newname[128];
            sprintf(newname, "%d", query->select_column[i].aggregate_method);
            strcat(newname, query->select_column[i].name);
            colid = ((Column*)g_catalog.getObjByName(newname))->getOid();
        }
        else
        {
            // for others, get the existed column
            colid = ((Column*)g_catalog.getObjByName(query->select_column[i].name))->getOid();    
        }
        output_colid.push_back(colid);
    }
    Project* proj = new Project();
    proj->setProjCol(input_colid, output_colid);
    proj->setChild((Operator*)Op_list[0]);
   
    return proj;
}

int Executor::exec(SelectQuery *query, ResultTable *result)
{
    if(query == NULL) return 0;
    // operator tree
    root = planner(query);
    // get the number of the output columns
    int colnum = ((Project *)root) -> getColnum();
    // init result table    
    result->init(((Project *)root) -> getSchema(), colnum, 1<<27);

    ((Project*)root)->top();
    root->open();
    char* top_buffer = root->getBuffer();
    
    int cnt = 0;
    while(root->getNext())
    {
        result->append(top_buffer);
    }

    return 1;
}

int Executor::close() 
{
    if(!root) return 1;
    root->close();
    delete root;
    return 0;
}

int ResultTable::append(char* src)
{
    char *p = getRC (row_number, 0);
    if (p==NULL) return 0;
    memcpy(p, src, row_length);
    row_number++;
    return 0;
}

// note: you should guarantee that col_types is useable as long as this ResultTable in use, maybe you can new from operate memory, the best method is to use g_memory.
int ResultTable::init(BasicType *col_types[], int col_num, int64_t capacity) {
    column_type = col_types;
    column_number = col_num;
    row_length = 0;
    buffer_size = g_memory.alloc (buffer, capacity);
    if(buffer_size != capacity) {
        printf ("[ResultTable][ERROR][init]: buffer allocate error!\n");
        return -1;
    }
    int allocate_size = 1;
    int require_size = sizeof(int)*column_number;
    while (allocate_size < require_size) {
        allocate_size = allocate_size << 1;
    }
    while(allocate_size < 8) {
        allocate_size = allocate_size << 1;
    }
    char *p = NULL;
    offset_size = g_memory.alloc(p, allocate_size);
    if (offset_size != allocate_size) {
        printf ("[ResultTable][ERROR][init]: offset allocate error!\n");
        return -2;
    }
    offset = (int*) p;
    for(int ii = 0;ii < column_number;ii ++) {
        offset[ii] = row_length;
        row_length += column_type[ii]->getTypeSize(); 
    }
    row_capicity = (int)(capacity / row_length);
    row_number   = 0;
    return 0;
}

int ResultTable::print (void) {
    int row = 0;
    int ii = 0;
    char buffer[1024];
    char *p = NULL; 
    while(row < row_number) {
        for( ; ii < column_number-1; ii++) {
            p = getRC(row, ii);
            column_type[ii]->formatTxt(buffer, p);
            printf("%s\t", buffer);
        }
        p = getRC(row, ii);
        column_type[ii]->formatTxt(buffer, p);
        printf("%s\n", buffer);
        row ++; ii=0;
    }
    return row;
}

int ResultTable::dump(FILE *fp) {
    // write to file
    int row = 0;
    int ii = 0;
    char buffer[1024];
    char *p = NULL; 
    while(row < row_number) {
        for( ; ii < column_number-1; ii++) {
            p = getRC(row, ii);
            column_type[ii]->formatTxt(buffer, p);
            fprintf(fp,"%s\t", buffer);
        }
        p = getRC(row, ii);
        column_type[ii]->formatTxt(buffer, p);
        
        fprintf(fp,"%s\n", buffer);
        row ++; ii=0;
    }
    return row;
}

// this include checks, may decrease its speed
char* ResultTable::getRC(int row, int column) {
    return buffer+ row*row_length+ offset[column];
}

int ResultTable::writeRC(int row, int column, void *data) {
    char *p = getRC (row,column);
    if (p==NULL) return 0;
    return column_type[column]->copy(p,data);
}

int ResultTable::shut (void) {
    // free memory
    g_memory.free (buffer, buffer_size);
    g_memory.free ((char*)offset, offset_size);
    return 0;
}

//---------------------operators implementation---------------------------
/**
 * @author zyl & dhk
 * 
 */

inline int64_t easyAlloc(int64_t size_want, char * & buf_to_alloc) {
    
    int64_t round_size = 1;
    // Round to 2^n
    while (round_size <= size_want) {
        round_size = round_size << 1;
    }

    char * new_buf;
    buf_to_alloc = (char*)malloc(round_size);
    /*if (g_memory.alloc(new_buf, round_size) != round_size) {
        buf_to_alloc = NULL;
        return -1;
    }
    */
    //buf_to_alloc = new_buf;
    return round_size;
}

/**
 * Get size of a tuple according to its column ID
 * @param  col_id    reference of column ID
 * @retval   size of this tuple
 */
inline int64_t getTupleSize(std::vector <int64_t> & col_id) {
    register int64_t tuple_size = 0;
    for (int64_t i = 0; i < col_id.size(); i ++){
        tuple_size += ((Column *)(g_catalog.getObjById(col_id[i]))) -> getCSize();
    }
    return tuple_size;
}

/**
 * Set record buffer for an input column
 * @param  col_id        reference of input column ID
 * @param  size_want     size of buffer, used for return
 * @param  buf_to_alloc  buffer to alloc, used for return
 * @retval  >0  actual allocated buffer size
 * @retval  <0  failure
 */
inline int64_t allocColBuf(std::vector <int64_t> & col_id, int64_t & size_want, char * & buf_to_alloc) {
    size_want = getTupleSize(col_id);
    
    return easyAlloc(size_want, buf_to_alloc);
}

/// Note: the dest buffer should be allocated
/// when first opened and keep static
bool Scan::open() {
    total_record = scan_table -> getRecordNum();
    next_record = 0;

    return true;
}

bool Scan::getNext() {
    for (int64_t i = next_record; i < total_record; i ++){
        // Find a valid record
        bool status = scan_table -> select(i, getBuffer());
        if (status) {
            next_record = i + 1;
            return true;
        }
    }

    return false;
}

bool Scan::close() {
    if (next_record < total_record) {
        return false;
    }

    // Release resources
    return true;
}

bool IndexScan::open() {
    i_type = this -> index -> getIType();
    if (i_type == HASHINDEX) {
        info_ptr = (void *)(new HashInfo);
    } else {
        info_ptr = (void *)(new PbtreeInfo);
    }

    return true;
}

void IndexScan::updateKey(void * search_key) {
    current_key = search_key;
    index -> set_ls(search_key, NULL, info_ptr);
    key_end = false;
}

bool IndexScan::getNext() {
    bool status;
    void * record = NULL;
    // Check if there is still a record for current key
    while((status = index -> lookup(current_key, info_ptr, record))){
        // If yes, write it to the buffer
        from -> select((char *)record, getBuffer());
        return true;
    }

    // No more record
    key_end = true;
    return false;
}

bool IndexScan::close() {
    // Not ended yet for current key
    if (!key_end) {
        return false;
    }

    if (i_type == HASHINDEX) {
        delete (HashInfo *) info_ptr;
    } else {
        delete (PbtreeInfo *) info_ptr;
    }
    return true;
}

void Filter::setFiltCond(int64_t filt_rank, CompareMethod cmp_mtd, char * value) {
    // Get offset for the filtering column
    int64_t off = 0;
    for (int64_t i = 0; i < filt_rank; i ++){
        Column * column = (Column *)(g_catalog.getObjById(input_cid[i]));
        off += column -> getCSize();
    }
    filt_off = off;

    // Set compare properties
    this -> cmp_mtd = cmp_mtd;
    cmp_func = this -> cmp_table[cmp_mtd];

    // Get datatype for the filtering column
    Column * filt_col = (Column *)g_catalog.getObjById((this -> input_cid)[filt_rank]);
    filt_type = filt_col -> getDataType();
    filt_type -> formatBin(this -> value, value);
}

bool Filter::open() {    
    // Alloc buffer for child
    if ((child_buf_size = allocColBuf(input_cid, in_tuple_size, buf_for_child)) < 0) {
        printf("[Filter][Error][open] Memory alloc failed.");
        return false;
    }

    this -> filt_pos = buf_for_child + filt_off;

    // Set up child
    child -> setBuffer(buf_for_child);
    return child -> open();
}

bool Filter::getNext() {
    // Check child iterately
    while(child -> getNext()) {
        // Check filter condition
        if (cmp_func(filt_pos, value, filt_type)){
            // Move from child to father
            memcpy(getBuffer(), buf_for_child, in_tuple_size);

            return true;
        }
    }

    // read to the end of child node
    return false;
}

bool Filter::close() {
    bool status = child -> close();
    if (status) {
        // Release resources
        delete child;
        free(buf_for_child);
    }
    return status;
}

bool IndexJoin::open() {
    // Set size for left
    std::vector <int64_t> & l_cid = getLeftCol();
    if ((left_buf_size = allocColBuf(l_cid, left_tuple_size, left_buf)) < 0) {
        printf("[IndexJoin][error][open] Memory alloc failed -1.");
        return false;
    }
    getLeftOp() -> setBuffer(left_buf);

    // Set size for right
    std::vector <int64_t> & r_cid = getRightCol();
    if ((right_buf_size = allocColBuf(r_cid, right_tuple_size, right_buf)) < 0) {
        printf("[IndexJoin][error][open] Memory alloc failed -2.");
        return false;
    }
    getRightOp() -> setBuffer(right_buf);

    // Open child operator
    bool status = getLeftOp() -> open();
    if (status == false) {
        return false;
    }
    status = getRightOp() -> open();
    if (status == false) {
        return false;
    }

    // Get offset of join key in right record
    int64_t offset = 0;
    for (int64_t i = 0; i < getRightRank(); i ++) {
        offset += ((Column * )(g_catalog.getObjById(getRightCol()[i]))) -> getCSize();
    }

    current_key = (void *)(right_buf + offset);
    
    // Fetch first record from right as initialization
    right_has_next = getRightOp() -> getNext();
    ((IndexScan *)getLeftOp()) -> updateKey(current_key);

    return true;
}

bool IndexJoin::getNext() {
    // Pump a record from left input
    bool left_has_next = getLeftOp() -> getNext();
    // End of left for current key, fetch a new record from right
    while (left_has_next == false) {
        right_has_next = getRightOp() -> getNext();
    
        // Both left and right has ended
        if (right_has_next == false) {
            return false;
        }

        // Update key
        ((IndexScan *)getLeftOp()) -> updateKey(current_key);
        left_has_next = getLeftOp() -> getNext();

    }
    // Right input has ended
    if (right_has_next == false) {
        return false;
    }

    // Neither left or right ended
    // Join left and right record
    memcpy(getBuffer(), left_buf, left_tuple_size);
    memcpy(getBuffer() + left_tuple_size, right_buf, right_tuple_size);
    return true;
}

bool IndexJoin::close() {
    bool status = (getLeftOp() -> close()) && (getRightOp() -> close());
    // Release resources
    if (status) {
        delete getLeftOp();
        delete getRightOp();

        free(left_buf);
        free(right_buf);
    }
    return status;
}

bool HashJoin::open() {
    // Open child operators
    bool status = getLeftOp() -> open();
    if (status == false) {
        return false;
    }
    status = getRightOp() -> open();
    if (status == false) {
        return false;
    }

    // Set buffer for right
    std::vector <int64_t> & r_cid = getRightCol();
    if ((right_buf_size = allocColBuf(r_cid, right_tuple_size, right_buf)) < 0) {
        printf("[HashJoin][error][open] Memory alloc failed -1.");
        return false;
    }
    getRightOp() -> setBuffer(right_buf);

    // Set join key from right
    right_key_type = ((Column *)g_catalog.getObjById(r_cid[getRightRank()])) -> getDataType();
    int64_t offset = 0, i = 0;
    for (; i < getRightRank(); i ++) {
        offset += ((Column *)g_catalog.getObjById(r_cid[i])) -> getCSize();
    }
    right_key_pos = right_buf + offset;

    // Set join key from left
    std::vector <int64_t> & l_cid = getLeftCol();
    left_key_type = ((Column *)g_catalog.getObjById(l_cid[getLeftRank()])) -> getDataType();
    offset = 0;
    for (i = 0; i < getLeftRank(); i ++) {
        offset += ((Column *)g_catalog.getObjById(l_cid[i])) -> getCSize();
    }
    left_key_off = offset;

    // Set buffer for intermediate result
    left_tuple_size = getTupleSize(l_cid);
    while (1) {
        // Alloc current buffer for a intermediate record
        if ((middle_buf_size = easyAlloc(left_tuple_size, left_buf)) < 0) {
            printf("[HashJoin][error][open] Memory alloc failed -2.");
            return false;
        }
        middle_buf_array.push_back(left_buf);

        // Set for left child
        getLeftOp() -> setBuffer(left_buf);

        // Keep pumping record from left
        if (getLeftOp() -> getNext() == false){
            break;
        }

        // If left has next, then add it to index
        left_key_type -> formatTxt(txt_buf, left_buf + left_key_off);

        hash_index.insert(std::pair <std::string, char *> (
            std::string(txt_buf),
            left_buf
            )
        );
    }

    // Read to the end of left, then fetch the first record from right
    right_has_next = getRightOp() -> getNext();

    // Set last-time iterator for searching from left
    right_key_type -> formatTxt(txt_buf, right_key_pos);
    last_iter = hash_index.lower_bound(txt_buf);
    upper_iter = hash_index.upper_bound(txt_buf);

    return true;
}

bool HashJoin::getNext() {
    // Use key from right to search from the left
    while (last_iter == upper_iter) {
        // End of left for current key
        // Fetch a new record from right
        right_has_next = getRightOp() -> getNext();

        // Both left and right has ended
        if (right_has_next == false) {
            return false;
        }

        // Update left search key
        right_key_type -> formatTxt(txt_buf, right_key_pos);

        last_iter = hash_index.lower_bound(txt_buf);
        upper_iter = hash_index.upper_bound(txt_buf);
    }
    // Right input has ended
    if (right_has_next == false) {
        return false;
    }

    // Neither left or right has ended
    // Join left and right record
    memcpy(getBuffer(), last_iter -> second, left_tuple_size);
    memcpy(getBuffer() + left_tuple_size, right_buf, right_tuple_size);
    // Consumed a left record
    last_iter ++;

    return true;
}

bool HashJoin::close() {
    bool status = (getRightOp() -> close()) && (last_iter == upper_iter) && (getLeftOp() -> close());
    // Release resources
    if (status) {
        // Note: multimap will be deleted after destructor is called
        delete getLeftOp();
        delete getRightOp();

        free(right_buf);

        for (int64_t i = 0; i < middle_buf_array.size(); i ++) {
            free(middle_buf_array[i]);
        }
    }
    return status;
}

bool Project::top() {
    int ii=0;
    int out_tuple_size = 0;
    char* buf_for_self;
    for (ii = 0; ii < output_cid.size(); ii ++) {
        Column * column = ((Column *)(g_catalog.getObjById(output_cid[ii])));
        out_tuple_size += column -> getCSize();
    }
    self_buf_size = easyAlloc(out_tuple_size, buf_for_self);
    setBuffer(buf_for_self);
    topid = true;
    return true;
}

bool Project::open() {
    // Get input tuple size and each column's offset and type
    int64_t i;
    int64_t tuple_size = 0;
    for (i = 0; i < input_cid.size(); i ++) {
        Column * column = ((Column *)(g_catalog.getObjById(input_cid[i])));
        input_off.push_back(tuple_size);
        input_type.push_back(column -> getDataType());
        tuple_size += column -> getCSize();
    }

    // Set buffer for child
    if ((in_buf_size = easyAlloc(tuple_size, buf_for_child)) < 0) {
        printf("[Project][error][open] Memory alloc failed.");
        return false;
    }

    // Get each output column's position
    for (i = 0; i < input_cid.size(); i ++) {
        input_pos.push_back(buf_for_child + input_off[i]);
    }

    // Set up child
    child -> setBuffer(buf_for_child);
    return child -> open();
}

bool Project::getNext() {
    bool status = child -> getNext();

    // Get an input record
    if (status) {
        // Now do project
        int64_t offset = 0;
        for (int64_t i = 0; i < output_cid.size(); i ++) {
            
            BasicType * proj_type = input_type[out_to_in[i]];
            proj_type -> copy(getBuffer() + offset, input_pos[out_to_in[i]]);
            offset += proj_type -> getTypeSize();
        }
    }
    return status;
}

bool Project::close() {
    // Whether Project can be closed depends on its child
    bool status = child -> close();
    if (status) {
        // Release resources
        free(getBuffer());
        free(buf_for_child);
        delete child;
    }
    return status;
}

bool GroupbyAggre::open() {
    // Open child operators
    bool status = child -> open();
    if (status == false) {
        return false;
    }

    // Set buffer for child
    if ((child_buf_size = allocColBuf(in_cid, child_tuple_size, buf_for_child)) < 0) {
        printf("[GroupbyAggre][error][open] Memory alloc failed -1.\n");
        return false;
    }
    child -> setBuffer(buf_for_child);

    // Collect column position from input
    std::vector < char * > in_pos;
    char * pos = buf_for_child;
    for (int64_t i = 0; i < in_cid.size(); i ++) {
        Column * column = (Column *)(g_catalog.getObjById(in_cid[i]));
        in_pos.push_back(pos);
        pos += column -> getCSize();
    }

    // Set type and pos for groupby
    middle_tuple_size = 0;
    for (int64_t i = 0; i < groupby_rank.size(); i ++) {
        Column * column = (Column *)(g_catalog.getObjById(in_cid[groupby_rank[i]]));
        group_by_type.push_back(column -> getDataType());
        group_by_pos.push_back(in_pos[groupby_rank[i]]);
        int64_t col_size = column -> getCSize();
        group_by_size.push_back(col_size);
        middle_tuple_size += col_size;
    }

    // Set type and pos for aggr
    for (int64_t i = 0; i < conditions.size(); i ++) {
        Column * column = (Column *)(g_catalog.getObjById(in_cid[conditions[i].column_rank]));
        aggr_type.push_back(column -> getDataType());
        aggr_pos.push_back(in_pos[conditions[i].column_rank]);
    }

    // Set aggregate method
    for (int64_t i = 0; i < conditions.size(); i ++) {
        auto type_code = aggr_type[i] -> getTypeCode();
        auto aggr_code = conditions[i].method;
        switch (aggr_code)
        {
        case COUNT:
            init_method[i] = &initCount;
            aggr_method[i] = &count;
            final_method[i] = &finalCount;
            break;
        case SUM:
            init_method[i] = &initSum;
            aggr_method[i] = sum_table[type_code];
            final_method[i] = final_sum_table[type_code];
            break;
        case AVG:
            init_method[i] = &initAvg;
            aggr_method[i] = sum_table[type_code];
            final_method[i] = final_avg_table[type_code];
            break;
        case MAX:
            init_method[i] = init_max_table[type_code];
            aggr_method[i] = max_table[type_code];
            final_method[i] = final_sum_table[type_code];
            break;
        case MIN:
            init_method[i] = init_min_table[type_code];
            aggr_method[i] = min_table[type_code];
            final_method[i] = final_sum_table[type_code];
            break;
        default:
            break;
        }
    }

    // Keep puming record from child
    while (child -> getNext()) {
        // Extract value from child to form the key
        group_by_key_t key = (group_by_key_t) {
            group_by_type,
            group_by_pos
        };

        // Already exists
        if (hash_group.find(key) != hash_group.end()) {
            // Add up
            auto record = hash_group[key];
            for (int64_t i = 0; i < conditions.size(); i ++) {
                aggr_method[i]((void *)(&((record -> sum)[i])), (void *)(&((record -> count)[i])), 
                aggr_pos[i]);
            }
        } else {
            // Create a new one
            char * new_record;

            if ((middle_buf_size = easyAlloc(middle_tuple_size, new_record)) < 0) {
                printf("[GroupbyAggre][error][open] Memory alloc failed -2.\n");
                return false;
            }
            middle_buf_array.push_back(new_record);

            // Copy to new buffer
            int64_t offset = 0;
            for (int64_t i = 0; i < groupby_rank.size(); i ++) {
                group_by_type[i] -> copy(new_record+offset, group_by_pos[i]);
                offset += group_by_type[i]->getTypeSize();
            }

            // Set this new class
            auto new_class = new GrAggRecord (new_record, conditions.size());
            hash_group[key] = new_class;
            for (int64_t i = 0; i < conditions.size(); i ++) {
                init_method[i]((void *)(&(new_class -> sum[i])), (void *)(&(new_class -> count[i])));
            }

            // Add up the first record
            for (int64_t i = 0; i < conditions.size(); i ++) {
                aggr_method[i]((void *)(&((new_class -> sum)[i])), (void *)(&((new_class -> count)[i])), 
                aggr_pos[i]);
            }
        }
    }

    // Finish
    next_iter = hash_group.begin();
    return true;
}

bool GroupbyAggre::getNext() {
    if (next_iter == hash_group.end()) {
        return false;
    }

    // There are still some record
    // Copy groupby columns
    auto aggr_data = next_iter -> second;
    memcpy(getBuffer(), aggr_data -> middle_record, middle_tuple_size);
    // Copy aggr results
    int64_t offset = middle_tuple_size;
    for (int64_t i = 0; i < conditions.size(); i ++) {
        // Compute the final result and copy to buffer
        final_method[i]((void *)(&(aggr_data -> sum[i])), (void *)(&(aggr_data -> count[i])), 
        (void *)(&(aggr_data -> sum[i])));
        aggr_type[i] -> copy(getBuffer() + offset, (void *)(&(aggr_data -> sum[i])));
        offset += aggr_type[i] -> getTypeSize();
    }

    next_iter ++;

    return true;
}

bool GroupbyAggre::close() {
    // Whether read to the end
    if (next_iter != hash_group.end()) {
        return false;
    }

    // Check child
    bool status = child -> close();
    if (status) {
        // Release resources
        free(buf_for_child);
        delete child;

        for (int64_t i = 0; i < middle_buf_array.size(); i ++) {
            free(middle_buf_array[i]);
        }
    }

    return status;
}

bool Orderby::open() {
    // the compare function for quicksort
    struct compare {
        int orderby_num;
        std::vector<BasicType*> coltype;
        std::vector<int> coloff;
        compare(int orderby_num, std::vector<BasicType*>& coltype, std::vector<int>& coloff) {
            this->orderby_num = orderby_num;
            this->coltype = coltype;
            this->coloff = coloff;
        }
        bool operator() (char * a, char * b) const {
            for(int i=0; i<orderby_num; i++)
            {
                if(coltype[i]->cmpLT(a+coloff[i], b+coloff[i]))
                {
                    return true;
                }
                else if(coltype[i]->cmpGT(a+coloff[i], b+coloff[i]))
                {
                    return false;
                }
            }
            return true;
        }
    };
    // Open child operators
    bool status = child -> open();
    if (status == false) {
        return false;
    }
    // the id to indicate which result to output
    arrayid = 0;
    // Set buffer for intermediate result
    tuple_size = getTupleSize(colid);
    while (1) {
        // Alloc current buffer for a intermediate record
        if ((middle_buf_size = easyAlloc(tuple_size, child_buffer)) < 0) {
            printf("[Orderby][error][open] Memory alloc failed -2.");
            return false;
        }
        // Set for left child
        child -> setBuffer(child_buffer);
        // Keep pumping record from left
        if (child -> getNext() == false){
            break;
        }
        middle_buf_array.push_back(child_buffer);
    }
    // get the offset and the type of order by column
    for(int i=0; i<orderby_num; i++)
    {
        Column* col = (Column*)g_catalog.getObjById(colid[colrank[i]]);
        coltype.push_back(col->getDataType());
        int offset = 0;
        for(int j=0; j<colrank[i]; j++)
        {
            offset += ((Column*)g_catalog.getObjById(colid[j]))->getDataType()->getTypeSize();
        }
        coloff.push_back(offset);
    }
    std::sort(middle_buf_array.begin(), middle_buf_array.end(), compare(orderby_num, coltype, coloff));
    return true;
}

bool Orderby::getNext() {
    // Extract value from intermediate results
    memcpy(getBuffer(), middle_buf_array[arrayid], tuple_size);
    arrayid++;
    if(arrayid>middle_buf_array.size()) 
        return false;
    return true;
}

bool Orderby::close() {
    // Release some resources
    bool status = (child -> close());
    if (status) {
        for (int64_t i = 0; i < middle_buf_array.size(); i ++) {
            free(middle_buf_array[i]);
        }
        delete child;
    }
    return status;
}

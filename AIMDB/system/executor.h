/**
 * @file    executor.h
 * @author  liugang(liugang@ict.ac.cn)
 * @version 0.1
 *
 * @section DESCRIPTION
 *  
 * definition of executor
 *
 */

#ifndef _EXECUTOR_H
#define _EXECUTOR_H

#include "catalog.h"
#include "mymemory.h"

#include <map>
#include <algorithm>
#include <float.h>

/** aggregate method. */
enum AggregateMethod {
    NONE_AM = 0, /**< none */
    COUNT,       /**< count of rows */
    SUM,         /**< sum of data */
    AVG,         /**< average of data */
    MAX,         /**< maximum of data */
    MIN,         /**< minimum of data */
    MAX_AM
};

/** compare method. */
enum CompareMethod {
    NONE_CM = 0,
    LT,        /**< less than */
    LE,        /**< less than or equal to */
    EQ,        /**< equal to */
    NE,        /**< not equal than */
    GT,        /**< greater than */
    GE,        /**< greater than or equal to */
    LINK,      /**< join */
    MAX_CM
};

/** definition of request column. */
struct RequestColumn {
    char name[128];    /**< name of column */
    AggregateMethod aggregate_method;  /** aggregate method, could be NONE_AM  */
};

/** definition of request table. */
struct RequestTable {
    char name[128];    /** name of table */
};

/** definition of compare condition. */
struct Condition {
    RequestColumn column;   /**< which column */
    CompareMethod compare;  /**< which method */
    char value[128];        /**< the value to compare with, if compare==LINK,value is another column's name; else it's the column's value*/
};

/** definition of conditions. */
struct Conditions {
    int condition_num;      /**< number of condition in use */
    Condition condition[4]; /**< support maximum 4 & conditions */
};

/** definition of aggregete condition. */
struct AggreCondition {
    int column_rank;        /**< the rank of the column has aggregate method */
    AggregateMethod method; /**< the aggregate method on that column */
};

/** definition of selectquery.  */
class SelectQuery {
  public:
    int64_t database_id;           /**< database to execute */
    int select_number;             /**< number of column to select */
    RequestColumn select_column[4];/**< columns to select, maximum 4 */
    int from_number;               /**< number of tables to select from */
    RequestTable from_table[4];    /**< tables to select from, maximum 4 */
    Conditions where;              /**< where meets conditions, maximum 4 & conditions */
    int groupby_number;            /**< number of columns to groupby */
    RequestColumn groupby[4];      /**< columns to groupby */
    Conditions having;             /**< groupby conditions */
    int orderby_number;            /**< number of columns to orderby */
    RequestColumn orderby[4];      /**< columns to orderby */
};  // class SelectQuery

/** definition of result table.  */
class ResultTable {
  public:
    int column_number;       /**< columns number that a result row consist of */
    BasicType **column_type; /**< each column data type */
    char *buffer;         /**< pointer of buffer alloced from g_memory */
    int64_t buffer_size;  /**< size of buffer, power of 2 */
    int row_length;       /**< length per result row */
    int row_number;       /**< current usage of rows */
    int row_capicity;     /**< maximum capicity of rows according to buffer size and length of row */
    int *offset;
    int offset_size;

    /**
     * init alloc memory and set initial value
     * @param  col_types array of column type pointers
     * @param  col_num   number of columns in this ResultTable
     * @param  capicity buffer_size, power of 2
     * @retval >0  success
     * @retval <=0  failure
     */
    int init(BasicType *col_types[],int col_num,int64_t capicity = 1024);
    /**
     * calculate the char pointer of data spcified by row and column id
     * you should set up column_type,then call init function
     * @param row    row id in result table
     * @param column column id in result table
     * @retval !=NULL pointer of a column
     * @retval ==NULL error
     */
    char* getRC(int row, int column);
    /**
     * write data to position row,column
     * @param row    row id in result table
     * @param column column id in result table
     * @param data data pointer of a column
     * @retval !=NULL pointer of a column
     * @retval ==NULL error
     */
    int writeRC(int row, int column, void *data);
    /**
     * print result table, split by '\t', output a line per row 
     * @retval the number of rows printed
     */
    int print(void);
    /**
     * write to file with FILE *fp
     */
    int dump(FILE *fp);
    /**
     * free memory of this result table to g_memory
     */
    int shut(void);

    /**
     * @brief write a row into result table
     * @param src the row buffer to be written
     * @return 1 success
     * @return 0 error
     */
    int append(char* src);
};  // class ResultTable

class Operator {
  public:
    char * buffer_from_father;  /** keep this buffer for passing record */

	public:
    /**
     * Constructor
     */
    Operator() {};
    /**
     * Destructor
     */
    virtual      ~Operator() {};

    /**
     * Save allocated buffer from father
     * should be called before this operator's open
     * @param buffer_allocated  a static buffer to store the next record
    */
    inline void setBuffer(char * buffer_allocated) {
      buffer_from_father = buffer_allocated;
    }
    /**
     * get current operator's buffer to write
    */
    inline char * getBuffer() {
      return buffer_from_father;
    }

    /**
     * open an operator
     * @retval true   successfully opened
     * @retval false  failed
     */
    virtual bool  open    () {};
    /**
     * get next record from the table iterately
     * @retval  true   success
     * @retval  false  failure
    */
    virtual bool  getNext () {};
    /**
     * close this operator, release all resources
     * @retval  true   success
     * @retval  false  failure
    */
    virtual bool  close   () {};
};

/** definition of class executor.  */
class Executor {
  private:
    SelectQuery *current_query;  /**< selectquery to iterately execute */
  public:
    Operator* root; /**< the root of the operator tree */
  public: 
    /**
     * generate operator tree and return root
     * @param query the query to form an operator tree
     * @retval the root of the operator tree
     */
    Operator* planner(SelectQuery *query);

    /**
     * @brief find the rank of selected column in given table
     * @param table_name the name of the given table
     * @param column_name the name of the selected column
     * @return the rank of the column in table
     */
    int findCol(char* table_name, char* column_name);

    /**
     * exec function.
     * @param  query to execute, if NULL, execute query at last time 
     * @param result table generated by an execution, store result in pattern defined by the result table
     * @retval >0  number of result rows stored in result
     * @retval <=0 no more result
     */
    int exec(SelectQuery *query, ResultTable *result);
    
    /**
     * close function.
     * @param None
     * @retval ==0 succeed to close
     * @retval !=0 fail to close
     */
    int close();

    /**
     * for a certain id vector, find the rank of a given id
     * @param vec the id vector to be searched through
     * @param id the id to be searched
     */
    int64_t getRank(std::vector < int64_t > &vec, int64_t id);
};

/**
 * Wrapped function of alloc
 * @param  size_want     size wanted to alloc
 * @param  buf_to_alloc  reference of new buffer addr
 * @retval  >0  actual allocated size
 * @retval  <0  failure
 */
inline int64_t easyAlloc(int64_t size_want, char * & buf_to_alloc);

/**
 * definition of the scan operator
 * located at the buttom of the op-tree
 */
class Scan : public Operator {
  private:
    Table * scan_table;  /** the designated table */
    int64_t total_record;  /** total record num of this table when first opened */
    int64_t next_record;  /** next record to check */
  public:
    /**
     * Constructor
    */
    Scan() {};
    /**
     * Destructor
    */
    ~Scan() {};

    /**
     * Set the table to scan
     * should be called before open
     * @param table the designated table
    */
    void setTable (Table * table) {
      scan_table = table;
    }
    /**
     * open a scan operator
     * @retval true   successfully opened
     * @retval false  failed
     */
    bool  open    ();
    /**
     * get next record from the table iterately
     * @retval  true   success
     * @retval  false  failure
    */
    bool  getNext ();
    /**
     * close this operator, release all resources
     * @retval  true   success
     * @retval  false  failure
    */
    bool  close   ();
};

/**
 * Definition of class IndexScan 
 */
class IndexScan : public Operator {
  private:
    Table * from;  /** From which table */
    Index * index;  /** Use which index */
    IndexType i_type;  /** Type of index */
    void * info_ptr;  /** Pointer of info, general type */
    void * current_key;  /** Current key for searching */
    bool key_end;  /** Searched to the end of current key */

  public:
    /**
     * Set designated table and index
     * @param table  table to scan
     * @param index  index to use when scanning
     */
    void setTabIdx(Table * table, Index * index) {
      this -> from = table;
      this -> index = index;
    }
    /**
     * Empty constructor, used together with `setTabIdx`
     */
    IndexScan() {};
    /**
     * Constructor, set table and index at the same time
     * @param table  table to scan
     * @param index  index to use when scanning
     */
    IndexScan(Table * table, Index * index) {
      setTabIdx(table, index);
    }
    /**
     * Destructor
     */
    ~IndexScan() {};
    /**
     * Open an IndexScan operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool open();
    /**
     * Update search key for IndexScan
     * Please use it before getNext
     * @param  search_key  new key to use
     */
    void updateKey(void * search_key);
    /**
     * Get next record from operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool getNext();
    /**
     * Close this operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool close();
};

/**
 * definition of the class Filter
 * the filter operator
*/
class Filter : public Operator {
  private:
    Operator * child;  /** child operator */
    char * buf_for_child;  /** buffer allocated for child */
    int64_t child_buf_size;  /** size of buffer for child */
    std::vector < int64_t > input_cid;  /** identifiers of columns to operate */
    char * filt_pos;  /** position of column to filter on */
    int64_t filt_off;  /** offset of column to filter on (with respect to the input) */
    CompareMethod cmp_mtd;  /** store compare method */
    BasicType * filt_type;  /** datatype of column to filter on */
    bool (*cmp_func) (void *a, void *b, BasicType * data_type);  /** compare function for filter */
    int64_t in_tuple_size;  /** size of the input tuple */
    char value[128];  /** save the binary value to compare with */

  private:
    /**
     * Jump table for comparison, data_type is used for dynamic binding
    */
    bool (*cmp_table [MAX_CM])(void * data1, void * data2, BasicType * data_type);
    /**
     * Comparision function for this class (wrapped method of BasicType)
     * Less than
     * @param data1      first operand
     * @param data2      second operand
     * @param data_type  basic type of these two operands
     * @retval  true     data1 < data2
     * @retval  false    otherwise
     */
    static inline bool cmpLT(void *data1, void *data2, BasicType * data_type){
      return data_type -> cmpLT(data1, data2);
    }
    /**
     * Less than or equal to
     * @param data1      first operand
     * @param data2      second operand
     * @param data_type  basic type of these two operands
     * @retval  true     data1 <= data2
     * @retval  false    otherwise
     */
    static inline bool cmpLE(void *data1, void *data2, BasicType * data_type){
      return data_type -> cmpLE(data1, data2);
    }
    /**
     * Equal to
     * @param data1      first operand
     * @param data2      second operand
     * @param data_type  basic type of these two operands
     * @retval  true     data1 == data2
     * @retval  false    otherwise
    */
    static inline bool cmpEQ(void *data1, void *data2, BasicType * data_type){
      return data_type -> cmpEQ(data1, data2);
    }
    /**
     * Not equal to
     * @param data1      first operand
     * @param data2      second operand
     * @param data_type  basic type of these two operands
     * @retval  true     data1 != data2
     * @retval  false    otherwise
    */
    static inline bool cmpNE(void *data1, void *data2, BasicType * data_type){
      return !(data_type -> cmpEQ(data1, data2));
    }
    /**
     * Greater than
     * @param data1      first operand
     * @param data2      second operand
     * @param data_type  basic type of these two operands
     * @retval  true     data1 > data2
     * @retval  false    otherwise
    */
    static inline bool cmpGT(void *data1, void *data2, BasicType * data_type){
      return data_type -> cmpGT(data1, data2);
    }
    /**
     * Greater than or equal to
     * @param data1      first operand
     * @param data2      second operand
     * @param data_type  basic type of these two operands
     * @retval  true     data1 >= data2
     * @retval  false    otherwise
    */
    static inline bool cmpGE(void *data1, void *data2, BasicType * data_type){
      return data_type -> cmpGE(data1, data2);
    }
    /**
     * Set properties for filter
     * @param  filt_rank the filter column is which column from the input
     * @param  cmp_mtd   compare method
     * @param  value     text value to compare with
    */
    void setFiltCond(int64_t filt_rank, CompareMethod cmp_mtd, char * value);
    /**
     * Initialize compare function
     */
    void initCmpFunc() {
      cmp_table[NONE_CM] = NULL;
      cmp_table[LT     ] = &cmpLT;
      cmp_table[LE     ] = &cmpLE;
      cmp_table[EQ     ] = &cmpEQ;
      cmp_table[NE     ] = &cmpNE;
      cmp_table[GT     ] = &cmpGT;
      cmp_table[GE     ] = &cmpGE;
      cmp_table[LINK   ] = NULL;
    }

  public:
    /**
     * Set child operator, shoud be called before open
     * @param child  child operator
    */
    void setChild(Operator * child) {
      this -> child = child;
    }
    /**
     * Save input column for later use, should be called before open
     * @param  c_id        the column id of input
     * @param  num_column  number of columns in the input
     * @param  filt_rank   the filter column is which column from the input
     * @param  cmp_mtd     compare method
     * @param  value       text of designated value
     */
    bool setColumn(int64_t c_id [], int64_t num_column, int64_t filt_rank, CompareMethod cmp_mtd, char * value) {
      if (cmp_mtd == NONE_CM || cmp_mtd == LINK || cmp_mtd == MAX_CM) {
        return false;
      }

      for (int64_t i = 0; i < num_column; i ++){
        input_cid.push_back(c_id[i]);
      }
      setFiltCond(filt_rank, cmp_mtd, value);

      return true;
    }
    /**
     * Overload, set input_cid
     * @param  input_cid   the column id of input
     * @param  filt_rank   the filter column is which column from the input
     * @param  cmp_mtd     compare method
     * @param  value       text of designated value
     * @retval  true       success
     * @retval  false      failure
     */
    bool setColumn(std::vector < int64_t > input_cid, int64_t filt_rank, CompareMethod cmp_mtd, char * value) {
      if (cmp_mtd == NONE_CM || cmp_mtd == LINK || cmp_mtd == MAX_CM) {
        return false;
      }

      this -> input_cid = input_cid;
      setFiltCond(filt_rank, cmp_mtd, value);

      return true;
    }

    /**
     * Constructor
    */
    Filter() {
      initCmpFunc();
    };
    /**
     * Destructor
    */
    ~Filter() {};
    /**
     * Overload constructor, designate some properties when created operator
     * @param  child      child operator to set
     * @param  input_cid  ID of input columns
     * @param  filt_rank  rank of filtering column from input
     * @param  cmp_mtd    compare method
     * @param  value      text of value compared with
     */
    Filter(Operator * child, std::vector < int64_t > input_cid, int64_t filt_rank, CompareMethod cmp_mtd, char * value) {
      initCmpFunc();
      setChild(child);
      setColumn(input_cid, filt_rank, cmp_mtd, value);
    }
    /**
     * Overload constructor
     * @param  child      child operator to set
     * @param  c_id       ID of input columns
     * @param  num_column number of input columns
     * @param  filt_rank  rank of filtering column from input
     * @param  cmp_mtd    compare method
     * @param  value      text of value compared with
    */
    Filter(Operator * child, int64_t c_id [], int64_t num_column, int64_t filt_rank, CompareMethod cmp_mtd, char * value) {
      initCmpFunc();
      setChild(child);
      setColumn(c_id, num_column, filt_rank, cmp_mtd, value);
    }

    /**
     * open a Filter operator
     * @retval  true   success
     * @retval  false  failure
    */
    bool open();
    /**
     * get next tuple iterately
     * @retval  true   success
     * @retval  false  failure
    */
    bool  getNext ();
    /**
     * close this operator, release all resources
     * @retval  true   success
     * @retval  false  failure
    */
    bool  close   ();

};

/** Definition of Join operator */
class Join : public Operator {
  private:
    Operator * left;  /** Left child operator, can only be scan */
    Operator * right;  /** Right child operator */

    std::vector < int64_t > left_cid;  /** Input column ID of left child */
    std::vector < int64_t > right_cid;  /** Input column ID of right child */

    int64_t left_rank;  /** Join Key is which column from the left child */
    int64_t right_rank;  /** Join Key is which column from the right child */

  public:
    /**
     * Constructor
     */
    Join() {}
    /**
     * Destructor
     */
    virtual ~Join() {}
    /**
     * Set left child operator
     * @param  lchild  operator to set
     */
    void setLeftOp(Operator * lchild) {
      left = lchild;
    }
    /**
     * Set right child operator
     * @param  rchild  operator to set
     */
    void setRightOp(Operator * rchild) {
      right = rchild;
    }
    /**
     * Get left child operator
     * @retval  reference of left child
     */
    Operator * & getLeftOp() {
      return left;
    }
    /**
     * Get right child operator
     * @retval  reference of right child
     */
    Operator * & getRightOp() {
      return right;
    }
    /**
     * Set input column arrangement
     * @param  left_cid    column ID of left input
     * @param  right_cid   column ID of right input
     * @param  left_rank   left rank to set
     * @param  right_rank  right rank to set
     */
    void setJoinCol(std::vector < int64_t > left_cid, std::vector < int64_t > right_cid, int64_t left_rank, int64_t right_rank) {
      this -> left_cid = left_cid;
      this -> right_cid = right_cid;
      this -> left_rank = left_rank;
      this -> right_rank = right_rank;
    }
    /**
     * Get left cid
     * @retval  left_cid
     */
    std::vector <int64_t> & getLeftCol() {
      return left_cid;
    }
    /**
     * Get right cid
     * @retval  right_cid
     */
    std::vector <int64_t> & getRightCol() {
      return right_cid;
    }
    /**
     * Get left rank
     * @retval  left_rank
     */
    int64_t getLeftRank() {
      return left_rank;
    }
    /**
     * Get right rank
     * @retval  right_rank
     */
    int64_t getRightRank() {
      return right_rank;
    }
    /**
     * Open a Join operator, IndexJoin or HashJoin
     * @retval  true   success
     * @retval  false  failure
     */
    virtual bool open() = 0;
    /**
     * Generate next record
     * @retval  true   success
     * @retval  false  failure
     */
    virtual bool getNext() = 0;
    /**
     * Close this operator
     * @retval  true   success
     * @retval  false  failure
     */
    virtual bool close() = 0;
};

/**
 * Definition of the class IndexJoin,
 * the `IndexNestedLoopJoin` operator
 */
class IndexJoin : public Join {
  private:
    int64_t left_tuple_size;  /** Size of each record from left */
    int64_t right_tuple_size;  /** Size of each record from right */

    char * left_buf;  /** Buffer allocated for left child */
    char * right_buf;  /** Buffer for right child */
    int64_t left_buf_size;  /** Left buffer size */
    int64_t right_buf_size;  /** Right buffer size */

    /** Note that we only support single-key index here */
    /** Keeps static after opening */
    void * current_key;  /** Current key for searching, that is, data in join key */
    bool right_has_next;  /** Whether read to the end of right input */

  public:
    /**
     * Constructor
     */
    IndexJoin() {}
    /**
     * Destructor
     */
    ~IndexJoin() {}

    /**
     * Overload constructor, set some attributes if already known
     * @param  left_cid   column ID from left
     * @param  right_cid  column ID from left
     * @param  left_rank  rank of join key from left
     * @param  right_rank rank of join key from right
     */
    IndexJoin(std::vector < int64_t > left_cid, std::vector < int64_t > right_cid, int64_t left_rank, int64_t right_rank) {
      setJoinCol(left_cid, right_cid, left_rank, right_rank);
    }

    /**
     * Open a IndexJoin operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool open();
    /**
     * Generate next record
     * @retval  true   success
     * @retval  false  failure
     */
    bool getNext();
    /**
     * Close this operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool close();
};

/**
 * Definition of class HashJoin
 * the simple `HashJoin` operator
 * with arbitrary left and right
 */
class HashJoin : public Join {
  private:
    char * left_buf;  /** Points to current area for left child to write */
    char * right_buf;  /** Buffer allocated for right child */

    std::vector <char *> middle_buf_array;  /** Each element points to an intermidiate record */
    int64_t middle_buf_size;  /** Size of each middle buffer */

    int64_t left_tuple_size;  /** Size of each record from left */
    int64_t right_tuple_size;  /** Size of each record from right */
    int64_t right_buf_size;  /** Size of buffer allocated for right */

    bool right_has_next;  /** Whether read to the end of right input */

    char * right_key_pos;  /** Position for join key from the right */
    int64_t left_key_off;  /** Offset for join key from the left */
    BasicType * right_key_type;  /** Basic type of join key on the right */
    BasicType * left_key_type;  /** Basic type of join key on the left */

    char txt_buf [128];  /** A buffer to receive text value */
    std::multimap <std::string, char *> hash_index;  /** Newly created hash index, will not be added to catalog */

    // Note: this should be arranged by lexicographic order
    std::multimap <std::string, char *> ::iterator last_iter;  /** Last time iterator when searching from the left */
    std::multimap <std::string, char *> ::iterator upper_iter;  /** Upper bound of iterator for current key */

  public:
    /**
     * Constructor
     */
    HashJoin() {}
    /**
     * Destructor
     */
    ~HashJoin() {}
    /*setIndex(std::vector < int64_t > left_cid, int64_t left_rank) {
      // Create a Hash Index
      //hash_index = new HashIndex()
    }*/
    /**
     * Overload constructor, set some attributes if already known
     * @param  left_cid   column ID from left
     * @param  right_cid  column ID from right
     * @param  left_rank  rank of join key from left
     * @param  right_rank rank of join key from right
     */
    HashJoin(std::vector < int64_t > left_cid, std::vector < int64_t > right_cid, int64_t left_rank, int64_t right_rank) {
      setJoinCol(left_cid, right_cid, left_rank, right_rank);
    }
    /**
     * Open a HashJoin operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool open();
    /**
     * Generate next record
     * @retval  true   success
     * @retval  false  failure
     */
    bool getNext();
    /**
     * Close this operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool close();
};

/**
 * Definition of class Project
 * the projection operator
 */
class Project : public Operator {
  private:
    Operator * child;  /** child operator */
    char * buf_for_child;  /** buffer allocated for child */

    int64_t self_buf_size;  /** buffer size allocated for operator on the top/self */

    std::vector < int64_t > input_cid;  /** input column ID */
    std::vector < int64_t > output_cid;  /** output column ID, can be reordered */
    std::vector < int64_t > out_to_in;  /** an output column is which column from input */

    int64_t in_tuple_size;  /** input tuple size */
    int64_t in_buf_size;  /** input buffer size */

    std::vector < BasicType * > input_type;  /** basic type of each column from input */
    std::vector < int64_t > input_off;  /** offset of each column from input */
    std::vector < char * > input_pos;  /** postion of each column from input */

    char * output_type;  /** basic type of each output column */
    int64_t output_type_size;  /** size of output type array */
    int64_t output_type_buf_size;  /** size of buffer allocated for *output_type */
    bool topid; /** the indicator to show that if this is the top of the operator tree */

  public:
    /**
     * Constructor
     */
    Project() {};
    /**
     * Destructor
     */
    ~Project() {
      g_memory.free(output_type, output_type_buf_size);
    };
    /**
     * Set child operator
     * @param  child  child operator to set
     */
    void setChild(Operator * child) {
      this -> child = child;
    }
    /**
     * Get output column number
     * @retval  size of output_cid
     */
    int64_t getColnum() {
      return output_cid.size();
    }
    /**
     * Get output data type
     * @retval  array of pointers to basic type of output columns
     */
    BasicType ** getSchema() {
      return (BasicType **)output_type;
    }

    /**
     * Set input and output columns
     * @param  in_cid   input column ID
     * @param  out_cid  output column ID
     */
    void setProjCol(std::vector <int64_t> in_cid, std::vector <int64_t> out_cid) {
      input_cid = in_cid;
      output_cid = out_cid;

      output_type_size = sizeof(BasicType *) * output_cid.size();
      //printf("set1");
      
      output_type_buf_size = easyAlloc(output_type_size, output_type);
      int64_t offset = 0;
      //printf("set2");
      

      for (int64_t i = 0; i < out_cid.size(); i ++) {
        // Add output's index from input
        auto iter = std::find(input_cid.begin(), input_cid.end(), out_cid[i]);
        out_to_in.push_back(std::distance(input_cid.begin(), iter));

        //printf("set(%d)\n", i);
        BasicType ** out_type = (BasicType **)(output_type + offset);
        Column * column = (Column *)(g_catalog.getObjById(output_cid[i]));
        *out_type = column -> getDataType();
        offset += sizeof(BasicType *);
      }
    }
    /**
     * Overload constructor, set some attributes if already known
     * @param  in_cid   input column ID
     * @param  out_cid  output column ID
     */
    Project(std::vector <int64_t> in_cid, std::vector <int64_t> out_cid) {
      setProjCol(in_cid, out_cid);
    }
    /**
     * Open a Project operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool open();
    /**
     * Generate next record
     * @retval  true   success
     * @retval  false  failure
     */
    bool getNext();
    /**
     * Close this operator
     * @retval  true   success
     * @retval  false  failure
     */
    bool close();
    /**
     * when this operator be the top of the operator tree, do this, set the buffer for self
     * @return true success
     * @return false failure
     */
    bool top();
};

/**
 * Definition of class GrAggRecord
 * an intermediate record of GroupbyAggre
 */
class GrAggRecord {
  public:
    char * middle_record;  /** Points to a class of middle record */
    std::vector <int64_t> sum;  /** Sum of this class, use int64 to store */
    std::vector <int64_t> count;  /** Count of this class, use int64 to store */

    /**
     * Constructor
     * @param middle_record  initial middle_record
     * @param num_aggr       number of aggr, size of sum/count
     */
    GrAggRecord(char * middle_record, int64_t num_aggr) {
      this -> middle_record = middle_record;
      sum.resize(num_aggr);
      count.resize(num_aggr);
    }
};

/**
 * Definition of class GroupbyAggre
 * the groupby-aggregation operator
 */
class GroupbyAggre : public Operator {
  private:
    Operator* child;    /** Child operator */
    std::vector <int64_t> in_cid;    /** Input column ID */
    std::vector <int64_t> groupby_rank;    /** Rank of group-by columns */
    std::vector <int64_t> out_cid;    /** Output column ID */
    std::vector <AggreCondition> conditions;    /** Aggregate method with column */

    char * buf_for_child;    /** Buffer for child */
    int64_t child_buf_size;    /** Buffer size allocated for child */
    int64_t child_tuple_size;    /** Size of a tuple from child */

    int64_t middle_tuple_size;  /** Size of an intermediate record, not including c and s */
    int64_t middle_buf_size;  /** Size of buffer allocated for intermediate record */
    std::vector < char * > middle_buf_array;    /** Each element points to an intermediate record */

    typedef std::vector < BasicType * > group_by_type_t;
    group_by_type_t group_by_type;  /** Basic type of group-by columns */
    std::vector < char * > group_by_pos;   /** Position of group-by columns */
    std::vector < int64_t > group_by_size;  /** Size of group-by columns */
    std::vector < BasicType * > aggr_type;  /** Basic type of aggr columns */
    std::vector < char * > aggr_pos;  /** Position of aggr columns */

    // Specify key type
    typedef struct group_by_key {
      group_by_type_t type_array;
      std::vector <char *> value_array;

      bool operator == (const group_by_key & k) const {
        int64_t count = 0;
        for (int64_t i = 0; i < value_array.size(); i ++) {
          if (k.type_array[i] -> cmpEQ(value_array[i], k.value_array[i])) {
            count ++;
          }
        }
        return count == value_array.size();
      }
    } group_by_key_t;
    // Specify hash function
    typedef struct group_by_hash {
      size_t operator() (const group_by_key_t &key) const {
        std::hash <std::string> hash_func;
        size_t hash_val = 0;
        char cmp_buffer[128];  /** A buffer used to compare a new record with existing record */
        for (int64_t i = 0; i < key.value_array.size(); i ++) {
          key.type_array[i] -> formatTxt(cmp_buffer, key.value_array[i]);
          hash_val ^= hash_func(std::string(cmp_buffer)) + 0xCafeBabe + (hash_val << 3) + (hash_val >> 1);
        }
        return hash_val;
      }
    } group_by_hash_t;
    std::unordered_map <group_by_key_t, GrAggRecord *, group_by_hash_t> hash_group;  /** Hash map for groupby */
    std::unordered_map <group_by_key_t, GrAggRecord *> ::iterator next_iter;  /** Iterator for next record */

    /**
     * Aggregate SUM method for INT8
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void sumInt8(void * sum, void * count, void * x) {
      *(int8_t *)sum += *(int8_t *)x;
    }
    /**
     * Aggregate SUM method for INT16
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void sumInt16(void * sum, void * count, void * x) {
      *(int16_t *)sum += *(int16_t *)x;
    }
    /**
     * Aggregate SUM method for INT32
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void sumInt32(void * sum, void * count, void * x) {
      *(int32_t *)sum += *(int32_t *)x;
    }
    /**
     * Aggregate SUM method for INT64
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void sumInt64(void * sum, void * count, void * x) {
      *(int64_t *)sum += *(int64_t *)x;
    }
    /**
     * Aggregate SUM method for FLOAT32
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void sumFloat32(void * sum, void * count, void * x) {
      *(float *)sum += *(float *)x;
    }
    /**
     * Aggregate SUM method for FLOAT64
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void sumFloat64(void * sum, void * count, void * x) {
      *(double *)sum += *(double *)x;
    }
    /** Search table for SUM */
    void (*sum_table [MAXTYPE_TC])(void * sum, void * count, void * x);

    /**
     * Aggregate AVG method for INT8
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void avgInt8(void * sum, void * count, void * x) {
      sumInt8(sum, count, x);
      *(int64_t *)count += 1;
    }
    /**
     * Aggregate AVG method for INT16
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void avgInt16(void * sum, void * count, void * x) {
      sumInt16(sum, count, x);
      *(int64_t *)count += 1;
    }
    /**
     * Aggregate AVG method for INT32
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void avgInt32(void * sum, void * count, void * x) {
      sumInt32(sum, count, x);
      *(int64_t *)count += 1;
    }
    /**
     * Aggregate AVG method for INT64
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void avgInt64(void * sum, void * count, void * x) {
      sumInt64(sum, count, x);
      *(int64_t *)count += 1;
    }
    /**
     * Aggregate AVG method for FLOAT32
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void avgFloat32(void * sum, void * count, void * x) {
      sumFloat32(sum, count, x);
      *(int64_t *)count += 1;
    }
    /**
     * Aggregate AVG method for FLOAT64
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to x to be added
     */
    static void avgFloat64(void * sum, void * count, void * x) {
      sumFloat64(sum, count, x);
      *(int64_t *)count += 1;
    }
    /** Search table for AVG */
    void (*avg_table [MAXTYPE_TC])(void * sum, void * count, void * x);
    /**
     * Aggregate MAX method for INT8
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void maxInt8(void * sum, void * count, void * x) {
      *(int8_t *)sum = std::max(*(int8_t *)sum, *(int8_t *)x);
    }
    /**
     * Aggregate MAX method for INT16
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void maxInt16(void * sum, void * count, void * x) {
      *(int16_t *)sum = std::max(*(int16_t *)sum, *(int16_t *)x);
    }
    /**
     * Aggregate MAX method for INT32
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void maxInt32(void * sum, void * count, void * x) {
      *(int32_t *)sum = std::max(*(int32_t *)sum, *(int32_t *)x);
    }
    /**
     * Aggregate MAX method for INT64
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void maxInt64(void * sum, void * count, void * x) {
      *(int64_t *)sum = std::max(*(int64_t *)sum, *(int64_t *)x);
    }
    /**
     * Aggregate MAX method for FLOAT32
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void maxFloat32(void * sum, void * count, void * x) {
      *(float *)sum = std::max(*(float *)sum, *(float *)x);
    }
    /**
     * Aggregate MAX method for FLOAT64
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void maxFloat64(void * sum, void * count, void * x) {
      *(double *)sum = std::max(*(double *)sum, *(double *)x);
    }
    /** Search table for MAX */
    void (*max_table [MAXTYPE_TC])(void * sum, void * count, void * x);
    /**
     * Aggregate MIN method for INT8
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void minInt8(void * sum, void * count, void * x) {
      *(int8_t *)sum = std::min(*(int8_t *)sum, *(int8_t *)x);
    }
    /**
     * Aggregate MIN method for INT16
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void minInt16(void * sum, void * count, void * x) {
      *(int16_t *)sum = std::min(*(int16_t *)sum, *(int16_t *)x);
    }
    /**
     * Aggregate MIN method for INT32
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void minInt32(void * sum, void * count, void * x) {
      *(int32_t *)sum = std::min(*(int32_t *)sum, *(int32_t *)x);
    }
    /**
     * Aggregate MIN method for INT64
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void minInt64(void * sum, void * count, void * x) {
      *(int64_t *)sum = std::min(*(int64_t *)sum, *(int64_t *)x);
    }
    /**
     * Aggregate MIN method for FLOAT32
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void minFloat32(void * sum, void * count, void * x) {
      *(float *)sum = std::min(*(float *)sum, *(float *)x);
    }
    /**
     * Aggregate MIN method for FLOAT64
     * @param  sum  pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x    pointed to x to be added
     */
    static void minFloat64(void * sum, void * count, void * x) {
      *(double *)sum = std::min(*(double *)sum, *(double *)x);
    }
    /** Search table for MIN */
    void (*min_table [MAXTYPE_TC])(void * sum, void * count, void * x);
    /**
     * Aggregate COUNT method
     * @param  sum   pointed to original value of sum
     * @param  count pointed to original value of count
     * @param  x     pointed to original value of sum
     */
    static void count(void * sum, void * count, void * x) {
      *(int64_t *)count += 1;
    }

    void (*aggr_method [4])(void * sum, void * count, void * x);    /** Aggregate method table */

    /** 
     * Init for SUM method, note that float 0.0 is encoded as 0
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initSum(void * sum, void * count) {
      *(int64_t *)sum = 0;
    }
    /**
     * Init for count method
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
     */
    static void initCount(void * sum, void * count) {
      *(int64_t *)count = 0;
    }
    /** 
     * Init for AVG method, note that float 0.0 is encoded as 0
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initAvg(void * sum, void * count) {
      initSum(sum, count);
      *(int64_t *)sum = 0;
    }
    /** 
     * Init for MIN method of INT8
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt8Min(void * sum, void * count) {
      *(int64_t *)sum = INT8_MAX;
    }
    /** 
     * Init for MIN method of INT16
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt16Min(void * sum, void * count) {
      *(int64_t *)sum = INT16_MAX;
    }
    /** 
     * Init for MIN method of INT32
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt32Min(void * sum, void * count) {
      *(int64_t *)sum = INT32_MAX;
    }
    /**
     * Init for MIN method of INT64
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt64Min(void * sum, void * count) {
      *(int64_t *)sum = INT64_MAX;
    }
    /** 
     * Init for MIN method of FLOAT32
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initFloat32Min(void * sum, void * count) {
      *(float *)sum = FLT_MAX;
    }
    /** 
     * Init for MIN method of FLOAT64
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initFloat64Min(void * sum, void * count) {
      *(double *)sum = DBL_MAX;
    }
    /** Search table for MIN init */
    void (*init_min_table [MAXTYPE_TC])(void * sum, void * count);
    /**
     * Init for MAX method of INT8
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt8Max(void * sum, void * count) {
      *(int64_t *)sum = INT8_MIN;
    }
    /** 
     * Init for MAX method of INT16
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt16Max(void * sum, void * count) {
      *(int64_t *)sum = INT16_MIN;
    }
    /** 
     * Init for MAX method of INT32
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt32Max(void * sum, void * count) {
      *(int64_t *)sum = INT32_MIN;
    }
    /** 
     * Init for MAX method of INT64
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initInt64Max(void * sum, void * count) {
      *(int64_t *)sum = INT64_MIN;
    }
    /**
     * Init for MAX method of FLOAT32
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initFloat32Max(void * sum, void * count) {
      *(float *)sum = FLT_MIN;
    }
    /**
     * Init for MAX method of FLOAT64
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void initFloat64Max(void * sum, void * count) {
      *(double *)sum = DBL_MIN;
    }
    /** Search table for MAX init */
    void (*init_max_table [MAXTYPE_TC])(void * sum, void * count);

    void (*init_method [4])(void * sum, void * count);    /** Init method table */

    /**
     * Finalize for SUM method of INT8
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalInt8Sum(void * sum, void * count, void * result) {
      *(int8_t *)result = *(int8_t *)sum;
    }
    /**
     * Finalize for SUM method of INT16
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalInt16Sum(void * sum, void * count, void * result) {
      *(int16_t *)result = *(int16_t *)sum;
    }
    /**
     * Finalize for SUM method of INT32
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void finalInt32Sum(void * sum, void * count, void * result) {
      *(int32_t *)result = *(int32_t *)sum;
    }
    /**
     * Finalize for SUM method of INT64
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalInt64Sum(void * sum, void * count, void * result) {
      *(int64_t *)result = *(int64_t *)sum;
    }
    /**
     * Finalize for SUM method of FLOAT32
     * @param  sum   sum to be initialized
     * @param  count count to be initialized
    */
    static void finalFloat32Sum(void * sum, void * count, void * result) {
      *(float *)result = *(float *)sum;
    }
    /**
     * Finalize for SUM method of FLOAT64
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalFloat64Sum(void * sum, void * count, void * result) {
      *(double *)result = *(double *)sum;
    }
    /** Search table for SUM final */
    void (*final_sum_table [MAXTYPE_TC])(void * sum, void * count, void * result);
    /**
     * Finalize for COUNT method
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalCount(void * sum, void * count, void * result) {
      *(int64_t *)result = *(int64_t *)count;
    }
    /**
     * Finalize for AVG method of INT
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalIntAvg(void * sum, void * count, void * result) {
      int64_t c = *(int64_t *)count;
      int64_t s = *(int64_t *)sum;
      *(double *)result = (double)s / (double)c;
    }
    /**
     * Finalize for AVG method of FLOAT32
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalFloat32Avg(void * sum, void * count, void * result) {
      int64_t c = *(int64_t *)count;
      float s = *(float *)sum;
      *(double *)result = (double)s / (double)c;
    }
    /**
     * Finalize for AVG method of FLOAT64
     * @param  sum     sum to be finalized
     * @param  count   count to be finalized
     * @param  result  place to save result
    */
    static void finalFloat64Avg(void * sum, void * count, void * result) {
      int64_t c = *(int64_t *)count;
      double s = *(double *)sum;
      *(double *)result = (double)s / (double)c;
    }
    /** Search table for AVG final */
    void (*final_avg_table [MAXTYPE_TC])(void * sum, void * count, void * result);

    void (*final_method [4])(void * sum, void * count, void * result);    /** Finalize method table */

  public:
    /**
     * Constructor
     * Initialize some data structure
    */
    GroupbyAggre() {
      sum_table[INT8_TC] = &sumInt8;
      sum_table[INT16_TC] = &sumInt16;
      sum_table[INT32_TC] = &sumInt32;
      sum_table[INT64_TC] = &sumInt64;
      sum_table[FLOAT32_TC] = &sumFloat32;
      sum_table[FLOAT64_TC] = &sumFloat64;

      avg_table[INT8_TC] = &avgInt8;
      avg_table[INT16_TC] = &avgInt16;
      avg_table[INT32_TC] = &avgInt32;
      avg_table[FLOAT32_TC] = &avgFloat32;
      avg_table[FLOAT64_TC] = &avgFloat64;

      max_table[INT8_TC] = &maxInt8;
      max_table[INT16_TC] = &maxInt16;
      max_table[INT32_TC] = &maxInt32;
      max_table[INT64_TC] = &maxInt64;
      max_table[FLOAT32_TC] = &maxFloat32;
      max_table[FLOAT64_TC] = &maxFloat64;

      min_table[INT8_TC] = &minInt8;
      min_table[INT16_TC] = &minInt16;
      min_table[INT32_TC] = &minInt32;
      min_table[INT64_TC] = &minInt64;
      min_table[FLOAT32_TC] = &minFloat32;
      min_table[FLOAT64_TC] = &minFloat64;

      init_min_table[INT8_TC] = &initInt8Min;
      init_min_table[INT16_TC] = &initInt16Min;
      init_min_table[INT32_TC] = &initInt32Min;
      init_min_table[FLOAT32_TC] = &initFloat32Min;
      init_min_table[FLOAT64_TC] = &initFloat64Min;

      init_max_table[INT8_TC] = &initInt8Max;
      init_max_table[INT16_TC] = &initInt16Max;
      init_max_table[INT32_TC] = &initInt32Max;
      init_max_table[FLOAT32_TC] = &initFloat32Max;
      init_max_table[FLOAT64_TC] = &initFloat64Max;

      final_sum_table[INT8_TC] = &finalInt8Sum;
      final_sum_table[INT16_TC] = &finalInt16Sum;
      final_sum_table[INT32_TC] = &finalInt32Sum;
      final_sum_table[INT64_TC] = &finalInt64Sum;
      final_sum_table[FLOAT32_TC] = &finalFloat32Sum;
      final_sum_table[FLOAT64_TC] = &finalFloat64Sum;

      final_avg_table[INT8_TC] = &finalIntAvg;
      final_avg_table[INT16_TC] = &finalIntAvg;
      final_avg_table[INT32_TC] = &finalIntAvg;
      final_avg_table[INT64_TC] = &finalIntAvg;
      final_avg_table[FLOAT32_TC] = &finalFloat32Avg;
      final_avg_table[FLOAT64_TC] = &finalFloat64Avg;
    };
    /**
     * Destructor
     */
    ~GroupbyAggre() {};

    /**
     * Set input and output columns
     * @param  input_colid   ID of input columns
     * @param  groupby_rank  rank of group-by columns from input
     * @param  conditions    aggregate method with its column
     * @param  output_colid  ID of output columns
     */
    void set (std::vector<int64_t> input_colid, std::vector<int64_t> groupby_rank, std::vector<AggreCondition> conditions, std::vector<int64_t> output_colid) {
      this -> in_cid = input_colid;
      this -> groupby_rank = groupby_rank;
      this -> conditions = conditions;
      this -> out_cid = output_colid;

    }
    /**
     * Set child operator, shoud be called before open
     * @param child  child operator
    */
    void setChild(Operator * child) {
      this -> child = child;
    }
    /**
     * open a scan operator
     * @retval true   successfully opened
     * @retval false  failed
     */
    bool  open    ();
    /**
     * get next record from the table iterately
     * @retval  true   success
     * @retval  false  failure
    */
    bool  getNext ();
    /**
     * close this operator, release all resources
     * @retval  true   success
     * @retval  false  failure
    */
    bool  close   ();
};

class Orderby : public Operator {
  private:
    Operator* child;        /**< child of this operator on the operator tree */
    std::vector<int64_t> colid; /**< the vector of input column id */
    std::vector<int> colrank;   /**< the rank of column needed to order by */
    std::vector<int> coloff;    /**< the offset of column needed to order by */
    std::vector<BasicType*> coltype; /**< the data type of column needed to order by */
    int orderby_num;            /**< the number of the column needed to order by */
    int64_t tuple_size;     /**< the size of one row from child result */
    int64_t middle_buf_size;    /**< the size of the middle result buffer */
    char* child_buffer;     /**< the buffer for the child result */
    std::vector<char*> middle_buf_array; /**< array of the middle result buffer */
    int arrayid;            /**< the id to indicate which one in the array shoud be the next result */
    int64_t self_buf_size;  /**< the size of the self buffer */
  public:
    /**
     * Constructor
    */
    Orderby() {};

    /**
     * Destructor
     */
    ~Orderby() {};

    /**
     * set the variables of Orderby
     * @param input_colid the input column id
     * @param orderby_rank the rank of the column needed to order by
     */
    void set (std::vector<int64_t> input_colid, std::vector<int> orderby_rank) {
      colid = input_colid;
      colrank = orderby_rank;
      orderby_num = colrank.size();
    }
    /**
     * Set child operator, shoud be called before open
     * @param child  child operator
    */
    void setChild(Operator * child) {
      this -> child = child;
    }
    /**
     * open a Orderby operator
     * @retval true   successfully opened
     * @retval false  failed
     */
    bool  open    ();
    /**
     * get next record from the table iterately
     * @retval  true   success
     * @retval  false  failure
    */
    bool  getNext ();
    /**
     * close this operator, release all resources
     * @retval  true   success
     * @retval  false  failure
    */
    bool  close   ();
};



#endif

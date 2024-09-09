#ifndef MATRIX_H
#define MATRIX_H

#include <cstddef>
#include <climits>
#undef NDEBUG
#include <assert.h>
#include <cstring>
#include "headers/statics/protExcept.h"
namespace protspace
{

template<class TYPE> class Matrix
{
protected:
    unsigned int mRows;
    unsigned int mColumns;
    unsigned int mMax;
    TYPE* mData;

public:
    ///
    /// \brief Constructor
    /// \param nRows Number of rows in the matrix
    /// \param nColumns Number of columns in the matrix
    /// \param def_val Default value for all element in the matrix
    ///
    /// Allocate a number of TYPE of nRows*nColumns and assign each element
    /// of the matrix with a default value of def_val
    ///
    Matrix(const size_t& nRows=1, const size_t& nColumns=1,const TYPE& def_val=0):
        mRows(nRows),
        mColumns(nColumns),
        mMax(nRows*nColumns),
        mData(new TYPE[mMax])
    {
        assert(nRows < UINT_MAX);
        assert(nColumns< UINT_MAX);
        assert(nRows*nColumns< UINT_MAX);
        for(size_t i=0;i<mMax;++i)mData[i]=def_val;
    }

    TYPE& at(const size_t& pos){return mData[pos];}


    ///
    /// \brief Copy constructor
    /// \param other Matrix to be copied
    ///
    Matrix(const Matrix<TYPE>& other):
        mRows(other.mRows),
        mColumns(other.mColumns),
        mMax(other.mMax),
        mData(new TYPE[mMax])

    {
        memcpy(mData,other.mData,other.mMax*sizeof(TYPE));
    }

    ~Matrix(){delete[] mData;}

    void setVal(const TYPE& def_val=0)
    {
            for(size_t i=0;i<mMax;++i)mData[i]=def_val;
    }
    /**
     * @brief resize the matrix to the given number of rows/columns
     * @param nRows Number of requested rows
     * @param nColumns Number of requested columns
     * @param def_val Default value
     * @throw 200101 Bad allocation
     */
    void resize(const size_t& nRows=20,
                const size_t& nColumns=20,
                const TYPE& def_val=0) throw(ProtExcept)
    {
        try{

            const size_t newMax=nRows*nColumns;
            mRows=nRows;
            mColumns=nColumns;
            if (newMax > mMax)
            {
                delete[]mData;
                mData=new TYPE[newMax];
            }
            mMax=newMax;

            for(size_t i=0;i<mMax;++i)mData[i]=def_val;
        }catch(std::bad_alloc &e)
        {
            std::string s("### BAD ALLOCATION ###\n");
            s+=e.what();s+="\n";
            throw_line("200101",
                       "Matrix::resize",s);
        }
    }

    ///
    /// \brief Gives the number of rows this matrix has
    /// \return Number of rows
    ///
    inline const unsigned int& numRows()const {return mRows;}


    ///
    /// \brief Gives the number of columns this matrix has
    /// \return Number of columns
    ///
    inline const unsigned int& numColumns()const{return mColumns;}



    inline const unsigned int& size() const {return mMax;}

    ///
    /// \brief Get the value of the given pair(row,columns) of the matrix
    /// \param row Row Position in the matrix of the wanted value
    /// \param column Column position in the matrix of the wanted value
    /// \return Value
    /// \throw 200202 - Given column above the number of columns
    /// \throw 200201 - Given row above the number of rows
    ///
    inline virtual TYPE& getVal(const size_t& row,
                                const size_t& column)const throw(ProtExcept)
    {
        if (row>=mRows)
            throw_line("200201",
                       "Matrix::GetVal",
                       "Given row above the number of rows");
        if (column>=mColumns)
            throw_line("200202",
                       "Matrix::GetVal",
                       "Given column above the number of columns");
        return mData[mColumns*row+column];
    }


    ///
    /// \brief Set the value of the element of the matrix defined by its row and its column
    /// \param row Row to be set
    /// \param column Column to be set
    /// \param value Value to be set to
    /// \param 200301 - Given row above the number of rows
    /// \param 200302 - Given column above the number of columns
    ///
    inline virtual void setVal(const size_t& row,
                               const size_t& column,
                               const TYPE& value)const throw(ProtExcept)
    {
        if (row>=mRows)
            throw_line("200301",
                       "Matrix::setVal",
                       "Given row above the number of rows");
        if (column>=mColumns)
            throw_line("200302",
                       "Matrix::setVal",
                       "Given column above the number of columns");
        mData[mColumns*row+column]=value;
    }
    inline virtual TYPE& val(const size_t& pos)
        {
            if (pos>=mMax)
                throw_line("200401",
                           "Matrix::val",
                           "Given position above the number of element in the matrix");
            return mData[pos];
        }
    inline virtual const TYPE& val(const size_t& pos)const
        {
            if (pos>=mMax)
                throw_line("200401",
                           "Matrix::val",
                           "Given position above the number of element in the matrix");
            return mData[pos];
        }

};

typedef Matrix<unsigned int> UIntMatrix;
typedef Matrix<double> DMatrix;
typedef  Matrix<int> IntMatrix;
typedef  Matrix<short> SMatrix;
typedef  Matrix<unsigned short> USMatrix;
typedef  Matrix<bool> BMatrix;

}

#endif // MATRIX_H


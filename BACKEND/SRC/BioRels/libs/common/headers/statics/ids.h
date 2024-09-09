#ifndef IDS_H
#define IDS_H

#include <iostream>
namespace protspace
{
///
/// \brief The ids class is a generic class that identify an object by two IDs
///
/// The ids class is a generic class that identify an object by two IDs:
/// the first one (mMId) is the one assigned by the program
/// whereas the second one (mFId) is assigned by the input file
///
///
class ids
{
protected:
    /**
     * @brief Id as assigned by the program
     *
     * Represent also the position of the related object in it's parent object
     */
    int mMId;

    /**
     * @brief Id as assigned by the input file
     */
    int mFId;

    /**
     * @brief set the current Id as assigned by the program
     * @param Id as assigned by the program
     * @warning This function shouldn't be used by any user.
     */
    void setMID(const int& MID) {mMId=MID;}


public:
    /**
     * @brief Standard constructor
     * @param MID Id as assigned by the program
     * @param FID Is as assigned by the input file.
     *
     * FID must be -1 if the related object is not originating from a file
     */
    ids(const int& MID, const int&FID):
        mMId(MID),mFId(FID){}


    /**
     * @brief get the current Id as assigned by the program
     * @return Id as assigned by the program
     * @test test_vertex
     */
    const int& getMID()const {return mMId;}


    /**
     * @brief get the current Id as given by the input file
     * @return Id as given by the input file
     * @test test_vertex
     */
    const int& getFID()const {return mFId;}


    /**
     * @brief set the current Id as given by the input file
     * @param Id as defined by the input file
     */
    void setFID(const int& FID) {mFId=FID;}


    virtual void serialize(std::ofstream& out) const =0;
};



}

#endif // IDS_H


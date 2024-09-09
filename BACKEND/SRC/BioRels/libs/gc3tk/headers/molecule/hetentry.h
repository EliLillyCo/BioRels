#ifndef HETENTRY_H
#define HETENTRY_H
#include <cstdint>
#include "headers/molecule/macromole.h"
#include "headers/math/matrix.h"
namespace protspace
{
class HETEntry
{
protected:
    ///
    /// \brief Structure of the monomer
    ///
    MacroMole mMolecule;
    ///
    /// \brief Name of the monomer.
    ///
    /// The name can the either the 3 Letters HET Code
    /// 
    ///
    std::string mName;

    ///
    /// \brief Current monomer name if mName is obsolete
    ///
    std::string mReplaced;

    ///
    /// \brief Class of the residue. see RESTYPE namespace.
    ///
    uint16_t mResClass;

    ///
    /// \brief Tautomeric form.
    ///
    unsigned char mTautomer;




    ///
    /// \brief Distance matrix of the monomer
    ///
    UIntMatrix mDistMatrix;

    bool mMatrixDone;
public:
    HETEntry(const std::string&name,
             const std::string& replace):
        mName(name),
        mReplaced(replace),
        mResClass(0x0000),
        mTautomer(0),
        mDistMatrix(80,80),
        mMatrixDone(false)
    {}

    HETEntry();
    void serialize(std::ofstream& ofs);
    void unserialize(std::ifstream& ifs, const bool &wMatrix=true);

    void setResClass(const uint16_t& classV){mResClass=classV;}
    const std::string& getName()const{return mName;}
    void calcDistMatrix();
    const MacroMole& getMole()const {return mMolecule;}
    MacroMole& getMole(){return mMolecule;}
    const std::string& getReplaced()const{return mReplaced;}
    bool isReplaced()const          {return (mReplaced.compare("/")!=0&&!mReplaced.empty());}
    const UIntMatrix& getMatrix()const{return mDistMatrix;}
    const uint16_t& getClass()const {return mResClass;}

};//END ENTRY
}

#endif ///HETENTRY_H

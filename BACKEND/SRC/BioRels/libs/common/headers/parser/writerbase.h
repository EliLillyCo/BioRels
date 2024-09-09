#ifndef WRITERBASE_H
#define WRITERBASE_H

#include <fstream>
#include "headers/statics/protExcept.h"
namespace protspace
{

class MMResidue;
class MacroMole;

class WriterBase
{

protected:
    ///
    /// \brief  Output file system handler
    ///
    std::ofstream mOfs;



    ///
    /// \brief Output file path
    ///
    std::string mPath;



    ///
    /// \brief When set to true, only output selected atom/residue/chain
    ///
    bool mOnlySelected;

    bool mAppendFile;



    std::vector<int> mChainToConsider;
    std::vector<int> mResidueToConsider;
    std::vector<int> mAtomToConsider;
    std::vector<int> mBondToConsider;

    /**
     * @brief open
     * @throw 450101   WriterBase::open     No Path given
     * @throw 450102   WriterBase::open     Unable to open file
     */
    void open() throw(ProtExcept);
    bool prepareResidue(const MMResidue& residue);
    void selectObjects(const MacroMole& molecule);
    bool getAtomPos(const size_t& pAtomMID,size_t& pos)const;
    bool getBondPos(const size_t& pBondMID,size_t& pos)const;
    bool getResiduePos(const size_t& pResidueMID,size_t& pos)const;
    bool getChainPos(const size_t& pChainMID,size_t& pos)const;
    WriterBase();

    ///
    /// \brief Standard constructor with predifined output file path
    /// \param path Path of the output file
    /// \param onlySelected
    ///
    WriterBase(const std::string& path, const bool& onlySelected=false);

public:

    virtual ~WriterBase();
    bool is_open(){return mOfs.is_open();}
    void setPath(const std::string &pPath);
    void close(){mOfs.close();}
    virtual void save(const MacroMole& mole)=0;
    void onlySelected(const bool& selection){mOnlySelected=selection;}
    void appendFile(const bool& append){mAppendFile=append;}
};

}

#endif // WRITERBASE_H


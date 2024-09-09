#ifndef SEQCHAIN_H
#define SEQCHAIN_H


#include "seqbase.h"
#include "headers/molecule/mmchain.h"
namespace protspace
{

class SequenceChain:public SeqBase
{
protected:
    MMChain& mChain;

    std::vector<short> mList;
    /**
     * @brief update
     * @throw 520101    SequenceChain::update   Unrecognized AA
     */
    void update();
public:

    /**
     * @brief SequenceChain
     * @param pChain
     * @throw 520101    SequenceChain::update   Unrecognized AA
     */
    SequenceChain(MMChain& pChain);
    ~SequenceChain();
    /**
     * @brief Return the Amino Acid at a given position of the sequence
     * @param position Position from which user wants the amino acid
     * @return Amido acid at the given position
     * @throw 520201 Position is above the number of character in the sequence
     */
    unsigned char posToName(const size_t& position)const throw(ProtExcept);



    /**
     * @brief Return the number of the Residue at a given position of the sequence
     * @param position Position from which the user wants the MMResidue ID
     * @return Residue ID
     * @throw 520301 Position is above the number of character in the sequence
     * @throw 330601 MMChain::getResidue Given position is above the number of MMResidue
     * In the case of a uniprot sequence, the number will be position+1
     * since position starts at 0 and number at 1. However, this might not
     * be the case in PDB files.
     */
    const int &posToId(const size_t& position)const throw(ProtExcept);


    /**
     * @brief Gives the length of the sequence
     * @return Number of characters in the sequence
     */
    size_t size() const;



    /**
     * @brief Return the Amino Acid at a given position of the sequence
     * @param pos Position from which user wants the amino acid
     * @return Amido acid at the given position
     * @warning will crash when pos > number of character in the sequence
     * Same as getSeqFromPosition()
     */
    const unsigned char& at(const size_t& pos) const;




    const char &getLetter(const size_t&pos)const;
    /**
     * @brief getResidue
     * @param pos
     * @return
     * @throw 520401    SequenceChain::getResidue    Position is above the number of entries
     * @throw 330601 MMChain::getResidue Given position is above the number of MMResidue
     */
    const  MMResidue& getResidue(const size_t& pos)const;

    /**
     * @brief getResidue
     * @param pos
     * @return
     * @throw 520501    SequenceChain::getResidue    Position is above the number of entries
     * @throw 330601 MMChain::getResidue Given position is above the number of MMResidue

     */
    MMResidue& getResidue(const size_t& pos);

    inline MMChain& getChain()const{return mChain;}


    inline const std::string& getMoleName()const{return mChain.getMoleName();}

    /**
     * @brief updateResName
     * @param resName
     * @param seqPos
     * @throw 520601   SequenceChain::updateResName    Unrecognized AA
     * @throw 520602   SequenceChain::updateResName    Given position above sequence length
     */
    void updateResName(const std::string& resName, const size_t& seqPos);

    bool getSeqPos(const MMResidue& res,size_t& value)const;



    void serialize(std::ofstream& ofs)const;
};
}
#endif // SEQCHAIN_H

#ifndef SEQSTD_H
#define SEQSTD_H

#include "seqbase.h"

namespace protspace
{

class Sequence:public SeqBase
{
    private:
    /**
     * @brief getResidue
     * @param pos
     * @return
     * @throw 510401    Sequence::getResidue        Standard sequence is not associated to a residue
     */
    MMResidue& getResidue(const size_t& pos);
protected:
    std::vector<long> mPos;
public:
    Sequence();
    Sequence(const Sequence& seq);
    Sequence(const Sequence& seq,std::vector<int>& map);
    Sequence(const std::string &pName);

    unsigned char posToName(const size_t& position)const throw(ProtExcept);

    const long & posToId(const size_t& position)const throw(ProtExcept);
    size_t size() const;
    const unsigned char &at(const size_t& pos) const;
    /**
     * @brief loadFastaSequence
     * @param sequence
     * @throw 510101    Sequence::loadFastaSequence       Header line should start with > for line
     * @throw 510102    Sequence::loadFastaSequence               Unrecognized AA
     */
    void loadFastaSequence(const std::vector<std::string> &sequence);
    /**
     * @brief loadSequence
     * @param line
     * @param keepGap
     * @throw 510201    Sequence::loadSequence    Unrecognized AA
     */
    void loadSequence(const std::string& line, const bool &keepGap=false, const bool &wGapShift=false);

    /**
     * @brief loadPIRSequence
     * @param sequence
     * @throw 510301    Sequence::loadPIRSequence       Header line should start with > for line
     * @throw 510302    Sequence::loadPIRSequence     Unrecognized AA
     */
    void loadPIRSequence(const std::vector<std::string> &sequence);
    void loadFastaFile(const std::string& file);
    const char &getLetter(const size_t&pos)const;

    void serialize(std::ofstream& ofs)const;
    void unserialize(std::ifstream& ifs);
    void clear();
    /**
     * @brief push_values
     * @param val
     * @param pos
     * @throw 500201    SeqBase::getPos     Unrecognized character
     */
    void push_values(const char& val,const long& pos);
    void push_values(const std::string&val, const long &pos);
    std::string toHumanString()const;
    size_t idToPos(const long &id) const;
    void setID(const int& pos, const long &id);
};
}
#endif // SEQSTD_H

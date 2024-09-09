#ifndef SEQNUCL_H
#define SEQNUCL_H

#include "seqbase.h"

namespace protspace
{

class SeqNucl:public SeqBase
{
    private:

protected:
    std::vector<long> mPos;
    void insertGap(const size_t &pos);
public:
    SeqNucl();
    SeqNucl(const SeqNucl& seq);
    SeqNucl(const SeqNucl& seq,std::vector<int>& map);
    SeqNucl(const std::string &pName);
    MMResidue& getResidue(const size_t& pos);
    unsigned char posToName(const size_t& position)const throw(ProtExcept);

    const long & posToId(const size_t& position)const throw(ProtExcept);
    size_t size() const;
    const unsigned char &at(const size_t& pos) const;
    /**
     * @brief loadFastaSeqNucl
     * @param sequence
     * @throw 510101    SeqNucl::loadFastaSeqNucl       Header line should start with > for line
     * @throw 510102    SeqNucl::loadFastaSeqNucl               Unrecognized AA
     */
    void loadFastaSeqNucl(const std::vector<std::string> &sequence);
    /**
     * @brief loadSeqNucl
     * @param line
     * @param keepGap
     * @throw 510201    SeqNucl::loadSeqNucl    Unrecognized AA
     */
    void loadSeqNucl(const std::string& line, const bool &keepGap=false, const bool &wGapShift=false);

    /**
     * @brief loadPIRSeqNucl
     * @param sequence
     * @throw 510301    SeqNucl::loadPIRSeqNucl       Header line should start with > for line
     * @throw 510302    SeqNucl::loadPIRSeqNucl     Unrecognized AA
     */
    void loadPIRSeqNucl(const std::vector<std::string> &sequence);
    void loadFastaFile(const std::string& file);
    const char &getLetter(const size_t&pos)const;
    std::string getSubSeq(const size_t& pos, const size_t& pLen)const;
    void getSubSeq(const size_t& pos, const size_t& pLen, std::string& pSeq)const;
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
    void replace(const size_t &pos, const char &letter);
    void push_back_gap();
    unsigned char getPos(const std::string &entry) const;
    unsigned char getPos(const char &entry);
    void loadSequence(const std::string &line);
};
struct TMP_NUCL_SEQ
{
    std::vector<long> mSEQ_DB;
    SeqNucl mSEQ;
};
}
#endif // SEQNUCL_H

#ifndef SEQBASE_H
#define SEQBASE_H
#define SEQB_LEN 26
#include "headers/statics/protExcept.h"
namespace protspace
{
class MMResidue;
class SeqBase
{
protected:
    /**
      * @brief Sequence name
      */
    std::string mName;


    /**
      * @brief Character used to consider gaps
      */
    char mGapChar;


    /**
      * @brief Sequence
      */
    std::vector<unsigned char> mSeq;
public:
    /**
      * @brief List of allowed characters in a sequence
      *
      */
    static const std::string mAASeq;
    static const unsigned char mAALen;
    static const std::string mNuclSeq;
    static const unsigned char mNuclLen;
    virtual ~SeqBase();

    /**
     * @brief Constructore
     * @param name Name of the sequence
     * @param len Length of the sequence
     * @param gapChar Character used for gap
     */
    SeqBase(const std::string& name,
            const size_t& len=600,
            const char& gapChar='-');

    /**
     * @brief Return the length of the sequence
     * @return Length of the sequence
     */
    inline size_t len()const {return mSeq.size();}

    inline const std::vector<unsigned char> & getSeq()const{return mSeq;}

    /**
     * @brief Gives the name of the sequence
     * @return Name of the sequence
     */
    inline const std::string& getName() const {return mName;}
    /**
     * @brief Assign a name to this sequence
     * @param name Name to assign to
     */
    inline void setName(const std::string &name){mName=name;}


    inline const char& getGapChar() const{return mGapChar;}

    /**
     * @brief Assign the gap character
     * @param gapChar Character to assign to
     */
    inline void setGapChar(const char& gapChar){mGapChar=gapChar;}


    /**
     * @brief toString
     * @return
     */
    std::string toString() const ;

    /**
     * @brief Convert this sequence into FASTA string
     * @return sequence in FASTA
     */
    std::string toFastaString() const;



    /**
     * @brief Return the Amino Acid at a given position of the sequence
     * @param position Position from which user wants the amino acid
     * @return Amido acid at the given position
     * @throw 300201 Position is above the number of character in the sequence
     */
    virtual unsigned char posToName(const size_t& position)const throw(ProtExcept)=0;


    /**
     * @brief Return the number of the Residue at a given position of the sequence
     * @param position Position from which the user wants the MMResidue ID
     * @return Residue ID
     * @throw 300401 Position is above the number of character in the sequence
     * In the case of a uniprot sequence, the number will be position+1
     * since position starts at 0 and number at 1. However, this might not
     * be the case in PDB files.
     */
    virtual  const long& posToId(const size_t& position)const throw(ProtExcept)=0;



    virtual MMResidue& getResidue(const size_t& pos)=0;


    /**
     * @brief Gives the length of the sequence
     * @return Number of characters in the sequence
     */
    virtual  size_t size() const=0;



    /**
     * @brief Return the Amino Acid at a given position of the sequence
     * @param pos Position from which user wants the amino acid
     * @return Amido acid at the given position
     * @warning will crash when pos > number of character in the sequence
     * Same as getSeqFromPosition()
     */
    virtual const unsigned char& at(const size_t& pos) const=0;

    virtual const char &getLetter(const size_t&)const =0;



    size_t ungapped_length() const;

    std::string ungapped_sequence() const ;
    bool  is_gap(const size_t& pos ) const ;

    char operator[](const size_t& pos ) const ;

    void insert(const size_t& pos, const char new_char ) ;

    /**
     * @brief getPos
     * @param entry
     * @return
     * @throw 500201    SeqBase::getPos     Unrecognized character
     */
    static unsigned char getPos(const char& entry);
    void remove( const size_t& pos );
    /**
     * @brief insertGap
     * @param pos
     * @throw  500401   SeqBase::insertGap              Position above length
     */
    void insertGap(const size_t& pos );
    void push_back(const char &val);
    void replace(const size_t& pos, const char& letter);
    void push_back_gap();

    /**
     * @brief getPos
     * @param entry
     * @return
     * @throw 500101    SeqBase::getPos     Unrecognized character
     */
    unsigned char getPos(const std::string& entry)const;
    bool hasGap()const;
    /**
     * @brief loadSequence
     * @param line
     * @throw 500301    SeqBase::loadFastaSequence    Unrecognized AA
     */
    void loadSequence(const std::string& line);

    friend std::ostream& operator<<(std::ostream& out, const SeqBase& val);

    bool hasAA() const;
};
}
#endif // SEQBASE_H

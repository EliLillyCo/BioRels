#ifndef UNIPROTENTRY_H
#define UNIPROTENTRY_H

#include <string>
#include <vector>
#include <map>
#include "headers/sequence/seqstd.h"


namespace protspace
{


/**
 * @brief The UniprotEntry class contains all information of a uniprot flat file
 */
class UniprotEntry
{
    friend class UniprotDBLoader;

public:

    /**
     * @brief The Feature struct describe a specific information at a given sequence range
     */
    struct Feature
    {
        /**
         * @brief FeatureType : Type of the Feature
         */
        std::string FeatureType;


        /**
         * @brief Verbose description
         */
        std::string Description;


        /**
         * @brief Position in the sequenece where this feature start
         */
        unsigned short start;


        /**
         * @brief Position in the sequenece where this feature ends
         */
        unsigned short end;

    };




private:

        typedef std::multimap<std::string,std::string>::const_iterator  filemapperit;

    /**
     * @brief Primary ACession number is a stable way of identifying entries
     *
     *
     * ACession number consists of 6 to 10 alphanumerical characters in the format:
     *
     * UniProtKB accession numbers consist of 6 or 10 alphanumerical characters in the format:
     *
     * 1              2 	  3 	    4           5           6       7       8           9           10
     * [A-N,R-Z] 	[0-9] 	[A-Z]       [A-Z, 0-9] 	[A-Z, 0-9] 	[0-9]
     * [O,P,Q]      [0-9] 	[A-Z, 0-9] 	[A-Z, 0-9] 	[A-Z, 0-9] 	[0-9]
     * [A-N,R-Z] 	[0-9] 	[A-Z] 	    [A-Z, 0-9] 	[A-Z, 0-9] 	[0-9] 	[A-Z] 	[A-Z,0-9] 	[A-Z,0-9] 	[0-9]
     *
     *  The three patterns can be combined into the following regular expression:
     * [OPQ][0-9][A-Z0-9]{3}[0-9]|[A-NR-Z][0-9]([A-Z][A-Z0-9]{2}[0-9]){1,2}
     */
    std::string mAC;


    /**
     * @brief List of Former Accession Number
     */
    std::vector<std::string> mListACs;


    /**
     * @brief All uniprot Data are stored by first 2 characters in the line
     *
     */
    std::multimap<std::string,std::string> mRawUniData;


    /**
     * @brief Uniprot Sequence
     */
    Sequence mSequence;


    /**
     * @brief List of features for this uniprot
     */
    std::vector<Feature> mListFeature;


    /**
     * @brief List of related PDB entries:
     */
    std::vector<std::string> mListPDB;



    /**
     * @brief Set to True when user wants getName() to return UniprotID
     */
    const bool mIsNameUniID;


    /**
     * @brief Enzyme number:
     */
    std::string mEC;


    /**
     * @brief Name of this entry
     */
    std::string mName;


    /**
     * @brief Uniprot Identifier (CDK4_HUMAN)
     */
    std::string mId;


    /**
     * @brief Set the name of the uniprot entry
     * @param value
     */
    void setName(const std::string& value){mName=value;}


    /**
     * @brief Get the uniprot sequence
     * @return Uniprot sequence
     */
    Sequence& processSequence()  {return mSequence;}


    /**
     * @brief Return the list of PDB entries related to this uniprot
     * @return List of PDB entries
     */
    std::vector<std::string> &processPDBList() {return mListPDB;}

    unsigned long mDBID;


public:



    /**
     * @brief Create a new uniprot object
     * @param ACtoLoad : Uniprot ACcession number to load
     * @throw 320101 given AC must not be empty
     */
    UniprotEntry(const std::string& ACtoLoad);



    void searchEC();


    const std::string& getEC()const {return mEC;}


    /**
     * @brief Read the uniprot flat file
     * @param mPath Path of the file
     * @throw 320201   Unable to open file
     * @throw 320202   Download error
     * @throw 320203   Download error
     * @throw 320204   Download error
     * @throw 320205 Unable to open file
     * @throw 300101 Sequence and number array must be of same size
     *
     * Download the file when not existing
     *
     */
    void loadData(const std::string& mPath="") throw(ProtExcept);

    void perceiveLines(const std::vector<std::string>& fFileLines);


    /**
     * @brief Give the associated uniprot sequence
     * @return Uniprot sequence
     */
    Sequence& getSequence()  {return mSequence;}



    /**
     * @brief Read from RawUniData table the full sequence and save it into this object sequence
     * @throw 300101 Sequence and number array must be of same size
     */
    void loadSequence() throw(ProtExcept);



    /**
     * @brief Read fron RawUniData table the features and save it into this object feature table
     */
    void loadFeatures();


    /**
     * @brief Check if the given AC is found in the lsit of ACs for this uniprot
     * @param AC Given ACcesssion number to check
     * @return TRUE when the given AC has been found,false otherwise
     */
    bool isACinList(const std::string&AC);



    /**
     * @brief Read fron RawUniData table the features and save it into this object AC list
     */
    void loadACs();



    /**
     * @brief Gives the name of this uniprot entry
     * @return Name of the uniprot entry
     */
    const std::string& getName()const {return mName;}



    /**
     * @brief Gives the list of PDB_ID related to this uniprot entry
     * @return List of PDB IDs
     */
    const std::vector<std::string> &getPDBList() const;



    /**
     * @brief Return the number of features defined in this uniprot entry
     * @return Number of feature
     */
    size_t numFeature() const { return mListFeature.size();}



    /**
     * @brief Returns a given feature
     * @param pos Position in the list of feature. Iust be between 0 and numFeature
     * @return Corresponding feature
     */
    const Feature& getFeature(const size_t& pos)const;



    /**
     * @brief Gives the primary AC of the entry
     * @return Primary AC
     */
    const std::string& getAC()const {return mAC;}


    const std::vector<std::string>& getListAC()const {return mListACs;}

    const std::pair<filemapperit,
         filemapperit> getData(const std::string& header)const ;

    std::string getUniprotID()const;
    std::string getGeneName()const;
    void getOrganism(std::string &val)const;
    void getTaxID(std::string &val)const;


};

}
#endif // UNIPROTENTRY_H

#include <fstream>
#include <iostream>
#include "headers/sequence/seqalign.h"
#include "gc3tk.h"
#include "headers/statics/strutils.h"
#include "headers/sequence/seqpair.h"


protspace::SMatrix protspace::SeqAlign::sSimMat(SEQB_LEN,SEQB_LEN);
bool protspace::SeqAlign::sMatLoaded=false;
std::string protspace::SeqAlign::sAltPath="";
void protspace::SeqAlign::setAltPath(const std::string &pDir){sAltPath=pDir;}
const protspace::SMatrix &protspace::SeqAlign::getMatrix(const bool& pIsProtein)
{
 if (!sMatLoaded)   loadMatrix(pIsProtein);
 return sSimMat;
}

protspace::SeqAlign::SeqAlign(SeqBase& refSeq, SeqBase& compSeq, const bool &pIsProtein):
    mRefSeq(refSeq),
    mCompSeq(compSeq),
    mExtendGapPenalty(-1),
    mGapOpenPenalty(-10),
    mThreshold(0)
{
    if (!sMatLoaded)loadMatrix(pIsProtein);
    if (refSeq.hasGap() || compSeq.hasGap())
        throw_line("540101",
                   "SeqAlign::SeqAlign",
                   "Sequence cannot have gaps");
    if (refSeq.len()==0)
        throw_line("540103",
                   "SeqAlign::SeqAlign",
                   "Reference sequence is empty");
    if (compSeq.len()==0)
        throw_line("540104",
                   "SeqAlign::SeqAlign",
                   "Compared sequence is empty");
    const size_t rSize(refSeq.size()+1);
    const size_t cSize(compSeq.size()+1);
    try{
        mScoreMat=new SeqMove[rSize*cSize];
        mBestMove=&mScoreMat[0];
    }catch(std::bad_alloc &e)
    {
        throw_line("540102","SeqAlign::SeqAlign","Bad allocation "+std::string(e.what()));
    }}

protspace::SeqAlign::~SeqAlign()
{
    delete[] mScoreMat;
}

void protspace::SeqAlign::loadMatrix(const bool& pIsProtein)
{
if (sMatLoaded)return;
std::string fPath("");
if (sAltPath==""){
    const std::string c3tk(getTG_DIR_STATIC());
    if (c3tk.empty())
        throw_line("540202","SeqAlign::loadMatrix","GC3TK NOT SET");
    if (pIsProtein)fPath=c3tk+"/EBLOSUM62";
    else fPath=c3tk+"/DNAFULL";
}else fPath=sAltPath+((pIsProtein)?"/EBLOSUM62":"/DNAFULL");
    std::ifstream ifs(fPath.c_str());
    if (!ifs.is_open()) throw_line("540201",
                                   "SeqAlign::loadMatrix",
                                   "Unable to load blossum file :"+ fPath);
    std::string ligne;
    size_t nline=0;
    std::vector<std::string> toks, heads;

    while(!ifs.eof())
    {
        std::getline(ifs,ligne);
        //cout <<ligne<<endl;
        if (ligne.length()==0||ligne.substr(0,1)=="#")continue;
        nline++;
        if (nline==1)
        {
            protspace::tokenStr(ligne,heads," ");
            continue;
        }

        toks.clear();
        protspace::tokenStr(ligne, toks," ");

        const size_t pos1=(pIsProtein)?protspace::SeqBase::mAASeq.find(toks.at(0)):protspace::SeqBase::mNuclSeq.find(toks.at(0));

        if (pos1== std::string::npos){std::cerr <<toks.at(0)<<" not found "<<std::endl;continue;}
        for(size_t i=1;i<toks.size();++i)
        {

            const size_t pos2=(pIsProtein)?protspace::SeqBase::mAASeq.find(heads.at(i-1)):protspace::SeqBase::mNuclSeq.find(heads.at(i-1));
            if (pos2== std::string::npos){std::cerr <<heads.at(i-1)<<" not found y "<<std::endl;continue;}

            sSimMat.setVal(pos1,pos2,atoi(toks.at(i).c_str()));

        }
    }sMatLoaded=true;


    ifs.close();
}



void protspace::SeqAlign::align(SeqPairAlign &scoreSchema)
try{
    mRefSeq.insertGap(0);
    mCompSeq.insertGap(0);
    const size_t refLen(mRefSeq.len());
    const size_t compLen(mCompSeq.len());


    for ( size_t y = 1; y < compLen; ++y ) {

        const unsigned char& letterComp=mCompSeq.at(y);

        for ( size_t x = 1; x < refLen; ++x ) {

            const unsigned char& letterRef=mRefSeq.at(x);
            // score this position
            double l_gap_penalty( mGapOpenPenalty ), u_gap_penalty( mGapOpenPenalty );
            if ( mScoreMat[x*compLen+y-1].mMove == above ) u_gap_penalty = mExtendGapPenalty;
            if ( mScoreMat[(x-1)*compLen+y].mMove == left  ) l_gap_penalty =mExtendGapPenalty;
            double u_gap = mScoreMat[x*compLen+(y-1)].mScore + u_gap_penalty;
            double l_gap = mScoreMat[(x-1)*compLen+   y ].mScore + l_gap_penalty;
            double mm    = mScoreMat[(x-1)*compLen+ y-1 ].mScore
                    + sSimMat.getVal(letterRef,letterComp);
            //Real mm    = scores( x-1, y-1 )->score() + ss->score( new_seq_x, new_seq_y, x, y ) - 0.45; // hacky

            //std::cout << mm << " = " << scores( x-1, y-1 )->score() << " + " << ss->score( new_seq_x, new_seq_y, x, y ) << std::endl;

            //std::cout << scores << std::endl;
            //std::cout << "(" << x << "," << y << ")"
            //     << " can choose from " << mm << "," << l_gap << "," << u_gap
            //     << std::endl;
            //std::cout << "mm = " << mm << "("
            // << scores( x-1, y-1 )->score() << "+"
            // << ss->score( new_seq_x, new_seq_y, x, y ) << ")"
            // << std::endl;
            SeqMove& current_cell = mScoreMat[x*compLen+y];
            current_cell.mX=x;current_cell.mY=y;
            if ( mm > l_gap && mm > u_gap && mm >= mThreshold ) {
                //if ( mm >= l_gap && mm >= u_gap && mm >= threshold ) {
                //std::cout << "came from diagonal with a score of " << mm << std::endl;
                current_cell.mScore= mm ;
                current_cell.mXNext=x-1;
                current_cell.mYNext=y-1;
                //                current_cell->next( scores(x-1,y-1) );
                current_cell.mMove= diagonal;
            } else if ( l_gap >= mm && l_gap >= u_gap && l_gap >= mThreshold ) {
                //std::cout << "came from left with a score of " << l_gap << std::endl;
                current_cell.mScore=l_gap;
                current_cell.mXNext=x-1;
                current_cell.mYNext=y;
                //                current_cell->next( scores(x-1,y) );
                current_cell.mMove=left;
            } else if ( u_gap >= mm && u_gap >= l_gap && u_gap >= mThreshold ) {
                //std::cout << "came from above with a score of " << u_gap << std::endl;
                current_cell.mScore=u_gap ;
                current_cell.mXNext=x;
                current_cell.mYNext=y-1;
                //                current_cell->next( scores(x,y-1) );
                current_cell.mMove=above ;
            } else {
                current_cell.mScore=mThreshold ;
                current_cell.mMove=end ;
            }

            if ( current_cell.mScore > mBestMove->mScore ) {
                mBestMove=&current_cell;
            }
        } // x
    } //
    traceback(scoreSchema);
}catch(ProtExcept &e)
{
    e.addHierarchy("SeqAlign::align");
    throw;
}


void protspace::SeqAlign::traceback(SeqPairAlign &scoreSchema) {
    // traceback
    SeqMove* current_cell = mBestMove;
    std::string aligned_seq_x(""), aligned_seq_y("");
    const size_t compLen(mCompSeq.len());
//    const size_t refLen(mRefSeq.len());
//for(size_t j=0;j<compLen;++j){
//    for(size_t i=0;i<refLen;++i)
//    std::cout <<mScoreMat[i*compLen+j].mScore<<" ";
//std::cout<<std::endl;
//}
    const short RS(mRefSeq.size());
    const short CS(mCompSeq.size());
    while ( 1 ) {
        const int& current_x = current_cell->mX;
        const int& current_y = current_cell->mY;
//        std::cout << "at " << current_x << "," << current_y << std::endl;
//        if (current_x <0 || current_y < 0)break;
//        if (current_x >=RS || current_y >= CS)break;

        if ( current_cell->mMove == diagonal ) {
//            std::cout << " came from diagonal from score of " << current_x<<" " <<current_y << std::endl;
            aligned_seq_x = mRefSeq.getLetter(current_x) + aligned_seq_x;
            aligned_seq_y = mCompSeq.getLetter( current_y ) + aligned_seq_y;
            scoreSchema.addPairToStart(current_x-1,current_y-1);

        } else if ( current_cell->mMove == left ) {
//            std::cout << " came from left from score of " << current_x <<" /" << std::endl;
            aligned_seq_x = mRefSeq.getLetter(current_x) + aligned_seq_x;
            aligned_seq_y = mCompSeq.getGapChar() + aligned_seq_y;
            scoreSchema.addPairToStart(current_x-1,-1);
            //            seq_y->insert_gap( current_y + 1 );
            // std::cout << "seq_x[" << current_x << "] = " << seq_x[current_x] << std::endl;
        } else if ( current_cell->mMove == above ) {
//            std::cout << " came from above from score of " << "/ "<<current_y << std::endl;
            aligned_seq_x = mRefSeq.getGapChar()+ aligned_seq_x;
            aligned_seq_y = mCompSeq.getLetter(current_y) + aligned_seq_y;
            scoreSchema.addPairToStart(-1,current_y-1);
            //            seq_x->insert_gap( current_x + 1 );
        } else {
            //std::string const msg(
            // "Unhandled case in traceback, not pointing to anything (" +
            // "\n"
            // //string_of(current_x) + "," +
            // //string_of(current_y) + ")!\n"
            //);
            //utility_exit_with_message( msg );
            //            utility_exit_with_message( "Error in traceback: pointer doesn't go anywhere!\n" );
        }
        if (mScoreMat[(current_cell->mXNext)*compLen+ current_cell->mYNext ].mMove==end)break;


        current_cell = &mScoreMat[(current_cell->mXNext)*compLen+ current_cell->mYNext ];
        //std::cout << aligned_seq_x << std::endl << aligned_seq_y << std::endl << std::endl;
    } // while ( current_cell->next() != 0 )

    //std::cout << std::endl << (*seq_x) << std::endl << (*seq_y) << std::endl;
    //std::cout << matrix << std::endl;
    //    std::cout << aligned_seq_x<<std::endl<<aligned_seq_y<<std::endl;
    mRefSeq.remove(0);
    mCompSeq.remove(0);
    //    SequenceAlignment alignment;
    //    // set starting point for both sequences. Don't forget to add whatever offset existed
    //    // in Sequence.start() coming into this function. Also, subtract one for the extra gap
    //    // inserted at the beginning of new_seq_x and new_seq_y.
    //    SequenceOP seq_x_clone = seq_x->clone();
    //    SequenceOP seq_y_clone = seq_y->clone();

    //    seq_x_clone->start( current_cell->x() - 2 + seq_x->start() );
    //    seq_y_clone->start( current_cell->y() - 2 + seq_y->start() );

    //    seq_x_clone->sequence( aligned_seq_x );
    //    seq_y_clone->sequence( aligned_seq_y );

    //    alignment.add_sequence( seq_y_clone );
    //    alignment.add_sequence( seq_x_clone );

    //    alignment.remove_gapped_positions();
    //    alignment.score( start->score() );

    //    return alignment;
} // traceback

